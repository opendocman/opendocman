# Creating a Theme for OpenDocMan

OpenDocMan uses a directory-based theme system.

## How It Works

Theme templates live in `application/views/<theme-name>/`. CSS/JS assets in `public/css/<theme-name>/` and `public/js/<theme-name>/`.

The `display_smarty_template()` function checks the theme directory first, then falls back to `application/views/common/`. This means:

- **Chrome templates** (header.tpl, footer.tpl, head_include.tpl, login.tpl) should be in your theme directory
- **Page content templates** live in `application/views/common/` and are shared across themes
- **Page-specific overrides**: place a `.tpl` file with the same name in your theme directory to override any common template

## Creating a New Theme

### Step 1: Copy the bootstrap5 theme

```
cp -r application/views/bootstrap5 application/views/<your-theme>
cp -r public/css/bootstrap5 public/css/<your-theme>
cp -r public/js/bootstrap5 public/js/<your-theme>
```

### Step 2: Update head_include.tpl

Edit `application/views/<your-theme>/head_include.tpl` to load your preferred CSS framework.

### Step 3: Override style.css and app.js

Edit `public/css/<your-theme>/style.css` and `public/js/<your-theme>/app.js` for your look and feel.

### Step 4: Optional page overrides

To customize a specific page, copy its `.tpl` from `common/` to your theme directory and modify it.

### Step 5: Activate the theme

Go to Settings in OpenDocMan and select your theme from the dropdown.

## Requirements

- No build step needed — CSS and JS are loaded via CDN
- Keep jQuery-free for compatibility with the bootstrap5 theme
- Use Bootstrap 5 compatible styles or provide your own framework