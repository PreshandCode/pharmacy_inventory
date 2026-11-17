<?php
// =====================================================================
//  PRODUCT CONTROLLER  (the page addresses keep their old "medicine" names)
//    index()   medicine.php          list with search / filters / sorting / pages
//    show()    view_medicine.php     details + stock history
//    create()  add_medicine.php      add form (GET) and save (POST)
//    edit()    edit_medicine.php     edit form
//    update()  update_medicine.php   save the edit form
//    destroy() delete_medicine.php   delete (admins only)
// =====================================================================

class MedicineController extends Controller
{
    // -----------------------------------------------------------------
    // LIST
    // -----------------------------------------------------------------
    public function index(): void
    {
        $days = EXPIRY_WARNING_DAYS;

        $stockOptions = [
            ""        => "All stock levels",
            "in"      => "In stock",
            "low"     => "Low stock",
            "out"     => "Out of stock",
            "low_out" => "Low + out of stock",
        ];
        $expiryOptions = [
            ""         => "All expiry dates",
            "good"     => "Good (over " . $days . " days)",
            "expiring" => "Expiring soon (" . $days . " days)",
            "expired"  => "Expired",
            "alert"    => "Expired + expiring soon",
            "none"     => "No expiry (devices / supplies)",
        ];

        // Read filters from the address bar - only allowed values are used
        $search   = mb_substr($this->query("search"), 0, 100);
        $type     = in_array($this->query("type"), Medicine::TYPES, true) ? $this->query("type") : "";
        $category = mb_substr($this->query("category"), 0, 50);
        $stock    = array_key_exists($this->query("stock"), $stockOptions) ? $this->query("stock") : "";
        $expiry   = array_key_exists($this->query("expiry"), $expiryOptions) ? $this->query("expiry") : "";
        $sort     = array_key_exists($this->query("sort"), Medicine::SORT_COLUMNS) ? $this->query("sort") : "name";
        $dir      = $this->query("dir") === "desc" ? "desc" : "asc";
        $perPage  = in_array((int) $this->query("per_page"), [10, 25, 50, 100], true) ? (int) $this->query("per_page") : ROWS_PER_PAGE;

        $model  = new Medicine($this->db);
        $result = $model->paginate(
            ["search" => $search, "type" => $type, "category" => $category, "stock" => $stock, "expiry" => $expiry],
            $sort, $dir, $perPage, (int) $this->query("page", "1")
        );

        // Current filters, used to build links (sorting, pages, clear)
        $params = [
            "search"   => $search,
            "type"     => $type,
            "category" => $category,
            "stock"    => $stock,
            "expiry"   => $expiry,
            "sort"     => $sort === "name" ? "" : $sort,
            "dir"      => $dir === "asc" ? "" : $dir,
            "per_page" => $perPage === ROWS_PER_PAGE ? "" : $perPage,
        ];

        $this->view("medicines/index", [
            "pageTitle"     => "Products",
            "medicines"     => $result["rows"],
            "totalRows"     => $result["total"],
            "page"          => $result["page"],
            "totalPages"    => $result["pages"],
            "offset"        => $result["offset"],
            "categories"    => $model->categories(),
            "types"         => Medicine::TYPES,
            "type"          => $type,
            "stockOptions"  => $stockOptions,
            "expiryOptions" => $expiryOptions,
            "search"        => $search,
            "category"      => $category,
            "stock"         => $stock,
            "expiry"        => $expiry,
            "sort"          => $sort,
            "dir"           => $dir,
            "params"        => $params,
            "hasFilters"    => $search !== "" || $type !== "" || $category !== "" || $stock !== "" || $expiry !== "",
            // This page's address, to come back here after deleting
            "returnUrl"     => build_url("medicine.php", $params + ["page" => $result["page"] > 1 ? $result["page"] : ""]),
        ]);
    }

    // -----------------------------------------------------------------
    // DETAILS
    // -----------------------------------------------------------------
    public function show(): void
    {
        $id  = $this->idFromQuery();
        $med = $this->findOrRedirect($id);

        $this->view("medicines/show", [
            "pageTitle"    => "Product details",
            "id"           => $id,
            "med"          => $med,
            "st"           => stock_status($med["quantity"]),
            "ex"           => expiry_status($med["expiry_date"]),
            "value"        => (float) $med["quantity"] * (float) $med["unit_price"],
            "history"      => (new StockMovement($this->db))->forMedicine($id, 15),
            "showMedicine" => false,
            "dash"         => '<span class="muted">—</span>',
        ]);
    }

    // -----------------------------------------------------------------
    // ADD (form + save)
    // -----------------------------------------------------------------
    public function create(): void
    {
        $model  = new Medicine($this->db);
        $errors = [];
        $form   = array_fill_keys(Medicine::FIELDS, "");
        $form["quantity"] = "0";
        $form["product_type"] = "Medicine";

        if ($this->isPost()) {
            csrf_check("add_medicine.php");

            $form   = $this->formInput();
            $errors = $model->validate($form, true);

            if (!$errors) {
                $newId = $model->create($form, current_user_id());
                (new AuditLog($this->db))->log("Add Product", "Added product: " . $form["medicine_name"] . " (ID: #" . $newId . ", Qty: " . $form["quantity"] . ")");
                flash("success", "\"" . $form["medicine_name"] . "\" was added.");
                redirect("view_medicine.php?id=" . $newId);
            }
            // Not valid: show the form again with the typed values and errors
        }

        $this->view("medicines/create", [
            "pageTitle"  => "Add product",
            "mode"       => "add",
            "form"       => $form,
            "errors"     => $errors,
            "categories" => $model->categories(),
            "suppliers"  => $model->suppliers(),
        ]);
    }

    // -----------------------------------------------------------------
    // EDIT (form)
    // -----------------------------------------------------------------
    public function edit(): void
    {
        $id       = $this->idFromQuery();
        $model    = new Medicine($this->db);
        $medicine = $this->findOrRedirect($id);

        // Values typed before a failed save (see update()), if any
        $old    = $_SESSION["form_old"][$id] ?? null;
        $errors = $_SESSION["form_errors"][$id] ?? [];
        unset($_SESSION["form_old"][$id], $_SESSION["form_errors"][$id]);

        $form = $old ?: [
            "medicine_name" => $medicine["medicine_name"],
            "product_type"  => $medicine["product_type"],
            "requires_rx"   => $medicine["requires_rx"] ? "1" : "",
            "category"      => $medicine["category"],
            "brand"         => $medicine["brand"] ?? "",
            "batch_number"  => $medicine["batch_number"] ?? "",
            "description"   => $medicine["description"] ?? "",
            "quantity"      => (string) $medicine["quantity"],
            "unit"          => $medicine["unit"] ?? "",
            "unit_price"    => $medicine["unit_price"],
            "expiry_date"   => $medicine["expiry_date"] ?? "",
            "supplier"      => $medicine["supplier"] ?? "",
        ];

        $this->view("medicines/edit", [
            "pageTitle"  => "Edit product",
            "mode"       => "edit",
            "id"         => $id,
            "medicine"   => $medicine,
            "form"       => $form,
            "errors"     => $errors,
            "categories" => $model->categories(),
            "suppliers"  => $model->suppliers(),
        ]);
    }

    // -----------------------------------------------------------------
    // EDIT (save)
    // -----------------------------------------------------------------
    public function update(): void
    {
        $this->requirePost("medicine.php");

        $id = (int) $this->input("medicine_id");
        $backToForm = "edit_medicine.php?id=" . $id;
        csrf_check($backToForm);

        if ($id <= 0) {
            flash("error", "Invalid product ID.");
            redirect("medicine.php");
        }

        $model  = new Medicine($this->db);
        $form   = $this->formInput();
        $errors = $model->validate($form, false, $id);

        if ($errors) {
            // Keep what was typed so it doesn't have to be typed again
            $_SESSION["form_old"][$id]    = $form;
            $_SESSION["form_errors"][$id] = $errors;
            redirect($backToForm);
        }

        $result = $model->update($id, $form, current_user_id());
        if ($result === null) {
            flash("error", "Product not found. It may have been deleted.");
            redirect("medicine.php");
        }

        (new AuditLog($this->db))->log("Edit Product", "Updated product: " . $form["medicine_name"] . " (ID: #" . $id . ")");

        $msg = "\"" . $form["medicine_name"] . "\" was updated.";
        if ($result["before"] !== $result["after"]) {
            $msg .= " Quantity changed from " . number_format($result["before"]) . " to " . qty_unit($result["after"], $form["unit"]) . ".";
        }
        flash("success", $msg);
        redirect("view_medicine.php?id=" . $id);
    }

    // -----------------------------------------------------------------
    // DELETE (admins only)
    // -----------------------------------------------------------------
    public function destroy(): void
    {
        $this->requirePost("medicine.php");
        csrf_check("medicine.php");
        $this->requireAdmin("medicine.php");

        $id = (int) $this->input("id");
        if ($id <= 0) {
            flash("error", "Invalid product ID.");
            redirect("medicine.php");
        }

        $name = (new Medicine($this->db))->delete($id);
        if ($name === null) {
            flash("error", "That product no longer exists.");
            redirect("medicine.php");
        }

        (new AuditLog($this->db))->log("Delete Product", "Deleted product: " . $name . " (ID: #" . $id . ")");

        flash("success", "\"" . $name . "\" was deleted.");

        // Go back to the same list page (with its filters) if it is one of ours
        $return = safe_next_page($this->input("return"));
        redirect(strpos($return, "medicine.php") === 0 ? $return : "medicine.php");
    }


    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    // The product form fields from $_POST
    private function formInput(): array
    {
        $form = [];
        foreach (Medicine::FIELDS as $field) {
            $form[$field] = $this->input($field);
        }
        return $form;
    }

    private function idFromQuery(): int
    {
        $id = (int) $this->query("id");
        if ($id <= 0) {
            flash("error", "Invalid product ID.");
            redirect("medicine.php");
        }
        return $id;
    }

    private function findOrRedirect(int $id): array
    {
        $med = (new Medicine($this->db))->find($id);
        if (!$med) {
            flash("error", "Product not found. It may have been deleted.");
            redirect("medicine.php");
        }
        return $med;
    }
}
