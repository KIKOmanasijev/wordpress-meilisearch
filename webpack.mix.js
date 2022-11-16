const mix = require('laravel-mix');
const tailwindcss = require('tailwindcss'); /* Add this line at the top */

mix
    .js("admin/src/js/wordpress-meilisearch-admin.js", "admin/dist/js/main.bundle.js")
    .postCss("admin/src/css/wordpress-meilisearch-admin.css", "admin/dist/css/main.css", [ tailwindcss("tailwind.config.js") ])
    .options({
        postCss: [ tailwindcss('./tailwind.config.js') ],
    })
;
