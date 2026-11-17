<?php
// =====================================================================
//  PRODUCT MODEL  (table: medicines)
//  The table keeps its old name "medicines" so nothing live breaks,
//  but it holds every product: medicines, vitamins, supplies, ...
//  Everything that reads or changes products: lists, filters, the
//  dashboard numbers, validation, saving, deleting and stock changes.
// =====================================================================

class Medicine extends Model
{
    // The form fields of a product
    public const FIELDS = [
        "medicine_name", "product_type", "requires_rx", "category", "brand", "batch_number", "description",
        "quantity", "unit", "unit_price", "expiry_date", "supplier",
    ];

    // Product types. Only these values are accepted.
    public const TYPES = [
        "Medicine", "Vitamins & Supplements", "Personal Care", "Baby & Mother Care", "First Aid",
        "Medical Supplies & Devices", "Beauty & Skin Care", "Food & Drinks", "Other",
    ];

    // How the quantity is counted. Only these values (or none) are accepted.
    public const UNITS = ["piece", "box", "bottle", "pack", "tube", "sachet", "strip", "tablet"];

    // Sorting: allowed choices -> real columns (user text never goes into SQL)
    public const SORT_COLUMNS = [
        "id"       => "medicine_id",
        "name"     => "medicine_name",
        "category" => "category",
        "quantity" => "quantity",
        "price"    => "unit_price",
        "expiry"   => "expiry_date",
        "added"    => "date_added",
    ];


    // =================================================================
    // READING
    // =================================================================

    public function find(int $id): ?array
    {
        return $this->one("SELECT * FROM medicines WHERE medicine_id = ?", "i", [$id]);
    }

    // All categories / suppliers in use, A–Z (filters and form suggestions)
    public function categories(): array
    {
        return array_column($this->all("SELECT DISTINCT category FROM medicines WHERE category <> '' ORDER BY category"), "category");
    }

    public function suppliers(): array
    {
        return array_column($this->all(
            "SELECT DISTINCT supplier FROM medicines WHERE supplier IS NOT NULL AND supplier <> '' ORDER BY supplier"
        ), "supplier");
    }

    // Short list for the Stock page dropdown
    public function allForSelect(): array
    {
        return $this->all(
            "SELECT medicine_id, medicine_name, batch_number, quantity, unit, expiry_date
             FROM medicines ORDER BY medicine_name ASC, expiry_date ASC"
        );
    }

    // Search + filter + sort + one page of results (Products page).
    //   $f = ["search", "type", "category", "stock", "expiry"]
    // Returns ["rows", "total", "page", "pages", "offset"]
    public function paginate(array $f, string $sort, string $dir, int $perPage, int $page): array
    {
        $low  = LOW_STOCK_THRESHOLD;
        $days = EXPIRY_WARNING_DAYS;
        $where = [];
        $types = "";
        $vals  = [];

        if ($f["search"] !== "") {
            $where[] = "(medicine_name LIKE ? OR brand LIKE ? OR batch_number LIKE ? OR supplier LIKE ?)";
            $like = like_pattern($f["search"]);
            $types .= "ssss";
            array_push($vals, $like, $like, $like, $like);
        }
        if ($f["type"] !== "") {
            $where[] = "product_type = ?";
            $types .= "s";
            $vals[] = $f["type"];
        }
        if ($f["category"] !== "") {
            $where[] = "category = ?";
            $types .= "s";
            $vals[] = $f["category"];
        }
        switch ($f["stock"]) {
            case "in":      $where[] = "quantity >= ?";                 $types .= "i"; $vals[] = $low; break;
            case "low":     $where[] = "quantity > 0 AND quantity < ?"; $types .= "i"; $vals[] = $low; break;
            case "out":     $where[] = "quantity <= 0"; break;
            case "low_out": $where[] = "quantity < ?";                  $types .= "i"; $vals[] = $low; break;
        }
        switch ($f["expiry"]) {
            case "expired":  $where[] = "expiry_date < CURDATE()"; break;
            case "expiring": $where[] = "expiry_date >= CURDATE() AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)";
                             $types .= "i"; $vals[] = $days; break;
            case "good":     $where[] = "expiry_date > DATE_ADD(CURDATE(), INTERVAL ? DAY)";
                             $types .= "i"; $vals[] = $days; break;
            case "alert":    $where[] = "expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)";
                             $types .= "i"; $vals[] = $days; break;
        }
        $whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

        $total = (int) $this->one("SELECT COUNT(*) AS total FROM medicines $whereSql", $types, $vals)["total"];
        $pages = max(1, (int) ceil($total / $perPage));
        $page  = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $orderSql = (self::SORT_COLUMNS[$sort] ?? "medicine_name") . " " . ($dir === "desc" ? "DESC" : "ASC") . ", medicine_id ASC";

        $rows = $this->all(
            "SELECT medicine_id, medicine_name, product_type, requires_rx, category, brand, batch_number, quantity,
                    unit, unit_price, expiry_date, supplier, date_added
             FROM medicines $whereSql
             ORDER BY $orderSql
             LIMIT ? OFFSET ?",
            $types . "ii",
            array_merge($vals, [$perPage, $offset])
        );

        return ["rows" => $rows, "total" => $total, "page" => $page, "pages" => $pages, "offset" => $offset];
    }


    // =================================================================
    // DASHBOARD
    // =================================================================

    public function dashboardStats(): array
    {
        return $this->one(
            "SELECT COUNT(*) AS total_medicines,
                    COALESCE(SUM(CASE WHEN expiry_date IS NULL OR expiry_date >= CURDATE() THEN quantity ELSE 0 END), 0) AS available_units,
                    COALESCE(SUM(quantity > 0 AND quantity < ?), 0) AS low_count,
                    COALESCE(SUM(quantity <= 0), 0) AS out_count,
                    COALESCE(SUM(expiry_date >= CURDATE() AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)), 0) AS expiring_count,
                    COALESCE(SUM(expiry_date < CURDATE()), 0) AS expired_count
             FROM medicines",
            "ii",
            [LOW_STOCK_THRESHOLD, EXPIRY_WARNING_DAYS]
        );
    }

    // Number of products of each type, in the order of TYPES (types with none are left out)
    public function typeCounts(): array
    {
        $counts = array_column(
            $this->all("SELECT product_type, COUNT(*) AS c FROM medicines GROUP BY product_type"),
            "c", "product_type"
        );
        $ordered = [];
        foreach (self::TYPES as $t) {
            if (!empty($counts[$t])) {
                $ordered[$t] = (int) $counts[$t];
            }
        }
        return $ordered;
    }

    // Low + out of stock, lowest first
    public function needsRestock(int $limit): array
    {
        return $this->all(
            "SELECT medicine_id, medicine_name, category, quantity, unit
             FROM medicines WHERE quantity < ?
             ORDER BY quantity ASC, medicine_name ASC LIMIT ?",
            "ii",
            [LOW_STOCK_THRESHOLD, $limit]
        );
    }

    // Expired + expiring within the warning window, earliest first
    public function expiringSoon(int $limit): array
    {
        return $this->all(
            "SELECT medicine_id, medicine_name, batch_number, quantity, unit, expiry_date
             FROM medicines WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
             ORDER BY expiry_date ASC, medicine_name ASC LIMIT ?",
            "ii",
            [EXPIRY_WARNING_DAYS, $limit]
        );
    }

    public function recentlyAdded(int $limit): array
    {
        return $this->all(
            "SELECT medicine_id, medicine_name, product_type, category, quantity, unit, date_added
             FROM medicines ORDER BY date_added DESC, medicine_id DESC LIMIT ?",
            "i",
            [$limit]
        );
    }


    // =================================================================
    // EXPIRY
    // =================================================================

    // Products with stock that are expired or expire within the critical window
    // (the red number on the sidebar)
    public function countUrgentExpiry(): int
    {
        $critical = min(EXPIRY_CRITICAL_DAYS, EXPIRY_WARNING_DAYS);
        return (int) $this->one(
            "SELECT COUNT(*) AS c FROM medicines
             WHERE quantity > 0 AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)",
            "i",
            [$critical]
        )["c"];
    }

    // Counts for the Expiry page tabs
    public function expiryCounts(int $criticalDays, int $warnDays): array
    {
        return $this->one(
            "SELECT COALESCE(SUM(expiry_date < CURDATE()), 0) AS expired,
                    COALESCE(SUM(expiry_date >= CURDATE() AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)), 0) AS critical,
                    COALESCE(SUM(expiry_date > DATE_ADD(CURDATE(), INTERVAL ? DAY) AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)), 0) AS soon,
                    COALESCE(SUM(expiry_date IS NOT NULL), 0) AS total
             FROM medicines",
            "iii",
            [$criticalDays, $criticalDays, $warnDays]
        );
    }

    // One page of the Expiry list, soonest expiry first.
    //   $status: attention | expired | critical | soon | all
    // Products without an expiry date are never listed.
    public function expiryList(string $status, string $search, string $category, int $criticalDays, int $warnDays, int $perPage, int $page): array
    {
        $where = ["expiry_date IS NOT NULL"];
        $types = "";
        $vals  = [];

        switch ($status) {
            case "attention":
                $where[] = "expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)";
                $types .= "i"; $vals[] = $warnDays; break;
            case "expired":
                $where[] = "expiry_date < CURDATE()"; break;
            case "critical":
                $where[] = "expiry_date >= CURDATE() AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)";
                $types .= "i"; $vals[] = $criticalDays; break;
            case "soon":
                $where[] = "expiry_date > DATE_ADD(CURDATE(), INTERVAL ? DAY) AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)";
                $types .= "ii"; array_push($vals, $criticalDays, $warnDays); break;
        }
        if ($search !== "") {
            $where[] = "(medicine_name LIKE ? OR brand LIKE ? OR batch_number LIKE ?)";
            $like = like_pattern($search);
            $types .= "sss";
            array_push($vals, $like, $like, $like);
        }
        if ($category !== "") {
            $where[] = "category = ?";
            $types .= "s"; $vals[] = $category;
        }
        $whereSql = "WHERE " . implode(" AND ", $where);

        $total = (int) $this->one("SELECT COUNT(*) AS c FROM medicines $whereSql", $types, $vals)["c"];
        $pages = max(1, (int) ceil($total / $perPage));
        $page  = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $rows = $this->all(
            "SELECT medicine_id, medicine_name, brand, batch_number, quantity, unit, unit_price, expiry_date
             FROM medicines $whereSql
             ORDER BY expiry_date ASC, medicine_name ASC
             LIMIT ? OFFSET ?",
            $types . "ii",
            array_merge($vals, [$perPage, $offset])
        );

        return ["rows" => $rows, "total" => $total, "page" => $page, "pages" => $pages, "offset" => $offset];
    }


    // =================================================================
    // VALIDATION
    // =================================================================

    // Check the product form.
    //   $isNew     - true when adding (expiry date may not be in the past)
    //   $excludeId - when editing, the product's own ID (for the duplicate check)
    // Returns ["field" => "message"]. Empty array = everything is valid.
    public function validate(array $d, bool $isNew, int $excludeId = 0): array
    {
        $errors = [];

        if ($d["medicine_name"] === "") {
            $errors["medicine_name"] = "Product name is required.";
        } elseif (mb_strlen($d["medicine_name"]) > 100) {
            $errors["medicine_name"] = "Product name must be 100 characters or fewer.";
        }

        if (!in_array($d["product_type"], self::TYPES, true)) {
            $errors["product_type"] = "Please choose a product type.";
        }
        if (!in_array($d["requires_rx"], ["", "1"], true)) {
            $errors["requires_rx"] = "Invalid prescription value.";
        }
        if ($d["unit"] !== "" && !in_array($d["unit"], self::UNITS, true)) {
            $errors["unit"] = "Please choose a unit from the list.";
        }

        if ($d["category"] === "") {
            $errors["category"] = "Category is required.";
        } elseif (mb_strlen($d["category"]) > 50) {
            $errors["category"] = "Category must be 50 characters or fewer.";
        }

        $limits = ["brand" => 100, "batch_number" => 50, "supplier" => 100, "description" => 2000];
        $labels = ["brand" => "Brand", "batch_number" => "Batch number", "supplier" => "Supplier", "description" => "Description"];
        foreach ($limits as $field => $max) {
            if (mb_strlen($d[$field]) > $max) {
                $errors[$field] = $labels[$field] . " must be " . $max . " characters or fewer.";
            }
        }

        // Quantity: whole number 0 .. MED_MAX_QUANTITY
        if ($d["quantity"] === "" || !preg_match('/^\d+$/', $d["quantity"])) {
            $errors["quantity"] = "Quantity must be a whole number (0 or more).";
        } elseif ((int) $d["quantity"] > MED_MAX_QUANTITY) {
            $errors["quantity"] = "Quantity cannot be more than " . number_format(MED_MAX_QUANTITY) . ".";
        }

        // Unit price: 0 or more, up to 2 decimal places
        if ($d["unit_price"] === "" || !preg_match('/^\d+(\.\d{1,2})?$/', $d["unit_price"])) {
            $errors["unit_price"] = "Unit price must be a number with up to 2 decimals (e.g. 12.50).";
        } elseif ((float) $d["unit_price"] > MED_MAX_PRICE) {
            $errors["unit_price"] = "Unit price is too large.";
        }

        // Expiry date: optional, but when given it must be a real calendar date
        if ($d["expiry_date"] !== "") {
            $date = DateTime::createFromFormat("!Y-m-d", $d["expiry_date"]);
            if (!$date || $date->format("Y-m-d") !== $d["expiry_date"]) {
                $errors["expiry_date"] = "Please enter a valid expiry date, or leave it empty if the product does not expire.";
            } elseif ($isNew && $date < new DateTime("today")) {
                $errors["expiry_date"] = "The expiry date is in the past. Expired products should not be added as new stock.";
            }
        }

        // Duplicate: same product name + same batch number
        if (!isset($errors["medicine_name"]) && $d["batch_number"] !== "") {
            $dup = $this->one(
                "SELECT medicine_id FROM medicines WHERE medicine_name = ? AND batch_number = ? AND medicine_id <> ? LIMIT 1",
                "ssi",
                [$d["medicine_name"], $d["batch_number"], $excludeId]
            );
            if ($dup) {
                $errors["batch_number"] = "This product with batch \"" . $d["batch_number"] . "\" already exists (ID #" . $dup["medicine_id"] . ").";
            }
        }

        return $errors;
    }


    // =================================================================
    // SAVING
    // Every change to the quantity is saved together with a history
    // record inside a transaction (all or nothing).
    // =================================================================

    // Add a product and record its starting stock. Returns the new ID.
    public function create(array $d, ?int $userId): int
    {
        $v = $this->dbValues($d);
        $this->db->begin_transaction();
        try {
            $this->exec(
                "INSERT INTO medicines
                    (medicine_name, product_type, requires_rx, category, description, brand, batch_number, quantity, unit, unit_price, expiry_date, supplier)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                "ssissssissss",
                [$v["name"], $v["type"], $v["rx"], $v["category"], $v["description"], $v["brand"], $v["batch"], $v["quantity"], $v["unit"], $v["price"], $v["expiry"], $v["supplier"]]
            );
            $id = (int) $this->db->insert_id;

            if ($v["quantity"] > 0) {
                (new StockMovement($this->db))->log($id, $v["name"], "in", 0, $v["quantity"], "Initial stock (new product)", $userId);
            }
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    // Save the edit form. Returns ["before" => old qty, "after" => new qty],
    // or null if the product no longer exists.
    public function update(int $id, array $d, ?int $userId): ?array
    {
        $v = $this->dbValues($d);
        $this->db->begin_transaction();
        try {
            // Lock the row while we change it
            $current = $this->one("SELECT quantity FROM medicines WHERE medicine_id = ? FOR UPDATE", "i", [$id]);
            if (!$current) {
                $this->db->rollback();
                return null;
            }
            $before = (int) $current["quantity"];

            $this->exec(
                "UPDATE medicines SET
                    medicine_name = ?, product_type = ?, requires_rx = ?, category = ?, description = ?, brand = ?, batch_number = ?,
                    quantity = ?, unit = ?, unit_price = ?, expiry_date = ?, supplier = ?
                 WHERE medicine_id = ?",
                "ssissssissssi",
                [$v["name"], $v["type"], $v["rx"], $v["category"], $v["description"], $v["brand"], $v["batch"], $v["quantity"], $v["unit"], $v["price"], $v["expiry"], $v["supplier"], $id]
            );

            if ($v["quantity"] !== $before) {
                (new StockMovement($this->db))->log($id, $v["name"], "adjust", $before, $v["quantity"], "Quantity corrected in Edit Product", $userId);
            }
            $this->db->commit();
            return ["before" => $before, "after" => $v["quantity"]];
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    // Delete a product. Its stock history is kept (medicine_id becomes empty).
    // Returns the deleted product's name, or null if it was not found.
    public function delete(int $id): ?string
    {
        $row = $this->one("SELECT medicine_name FROM medicines WHERE medicine_id = ?", "i", [$id]);
        if (!$row) {
            return null;
        }
        $this->exec("DELETE FROM medicines WHERE medicine_id = ?", "i", [$id]);
        return $row["medicine_name"];
    }

    // Stock in / out / set count, with all the safety rules:
    //   - never below 0, never more than is available
    //   - no stock added to an expired batch (products without an expiry date are never expired)
    //   - the row is locked so two people can't change it at the same moment
    // $type: "in", "out" or "set".
    // Returns ["error" => "..."] or ["name", "unit", "before", "after"].
    public function changeStock(int $id, string $type, int $qty, string $note, ?int $userId): array
    {
        $this->db->begin_transaction();
        try {
            $med = $this->one(
                "SELECT medicine_id, medicine_name, quantity, unit, expiry_date FROM medicines WHERE medicine_id = ? FOR UPDATE",
                "i",
                [$id]
            );
            if (!$med) {
                $this->db->rollback();
                return ["error" => "That product no longer exists.", "missing" => true];
            }

            $name   = $med["medicine_name"];
            $before = (int) $med["quantity"];
            $error  = "";

            if ($type === "in") {
                $after = $before + $qty;
                if ($med["expiry_date"] !== null && $med["expiry_date"] < date("Y-m-d")) {
                    $error = "\"" . $name . "\" expired on " . format_date($med["expiry_date"])
                           . ". You cannot add stock to an expired batch. Add the new delivery as a new product with its own batch and expiry date.";
                } elseif ($after > MED_MAX_QUANTITY) {
                    $error = "The new total would be more than " . qty_unit(MED_MAX_QUANTITY, $med["unit"]) . ".";
                }
            } elseif ($type === "out") {
                $after = $before - $qty;
                if ($before === 0) {
                    $error = "\"" . $name . "\" is out of stock. There is nothing to remove.";
                } elseif ($after < 0) {
                    $error = "Only " . qty_unit($before, $med["unit"]) . " of \"" . $name . "\" available. You cannot remove " . number_format($qty) . ".";
                }
            } else {
                $after = $qty;
                if ($after === $before) {
                    $error = "The quantity is already " . qty_unit($before, $med["unit"]) . ". Nothing was changed.";
                }
            }

            if ($error !== "") {
                $this->db->rollback();
                return ["error" => $error];
            }

            $this->exec("UPDATE medicines SET quantity = ? WHERE medicine_id = ?", "ii", [$after, $id]);
            (new StockMovement($this->db))->log($id, $name, $type === "set" ? "adjust" : $type, $before, $after, $note, $userId);

            $this->db->commit();
            return ["name" => $name, "unit" => $med["unit"], "before" => $before, "after" => $after];
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    // Form text -> values ready for the database (empty optional fields -> NULL)
    private function dbValues(array $d): array
    {
        $orNull = function (string $v): ?string {
            return $v !== "" ? $v : null;
        };
        return [
            "name"        => $d["medicine_name"],
            "type"        => $d["product_type"],
            // Prescription only applies to medicines
            "rx"          => ($d["product_type"] === "Medicine" && $d["requires_rx"] === "1") ? 1 : 0,
            "unit"        => $orNull($d["unit"]),
            "category"    => $d["category"],
            "description" => $orNull($d["description"]),
            "brand"       => $orNull($d["brand"]),
            "batch"       => $orNull($d["batch_number"]),
            "supplier"    => $orNull($d["supplier"]),
            "quantity"    => (int) $d["quantity"],
            "price"       => $d["unit_price"],      // exact decimal text, e.g. "12.50"
            "expiry"      => $orNull($d["expiry_date"]),
        ];
    }
}
