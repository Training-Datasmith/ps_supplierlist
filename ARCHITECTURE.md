# Architecture: ps_supplierlist

## Purpose

A PrestaShop front-office widget module that displays a list of product suppliers (brands) with logos and links to their supplier pages.

## Directory Structure

```
ps_supplierlist.php             - Module class; hook listeners and widget rendering
views/templates/hook/           - Smarty template for the supplier list widget
views/templates/_partials/      - Reusable partial templates (individual supplier item)
tests/                          - PHPUnit test stubs and PHPStan bootstrap
translations/                   - Locale string overrides
```

## Key Design Decisions

- **WidgetInterface**: Implements `WidgetInterface` for theme-editor positioning.
- **Supplier images**: Resolves supplier logo image paths via PrestaShop's media URL helpers.
- **Alphabetical ordering**: Suppliers are listed alphabetically by name by default.

## Extension Points

- Override `getWidgetVariables()` to filter by supplier active status or add custom sorting.

## Dependency Flow

```
ps_supplierlist (Module + WidgetInterface)
  └─> renderWidget()            — renders the supplier list template
        └─> getWidgetVariables()
              └─> Supplier::getSuppliers()
```
