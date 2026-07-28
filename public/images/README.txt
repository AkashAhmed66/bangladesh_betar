Dashboard hero banner
=====================

Save the Bangladesh Betar banner image in THIS folder as:

    dashboard-hero.jpg

The admin dashboard hero (resources/views/admin/dashboard.blade.php) uses it as
a background at the root-relative path /images/dashboard-hero.jpg, with a dark
brand-tinted overlay so the white text stays readable.

A wide banner (roughly 4000x900, ~4.3:1) works best. JPG keeps the file small;
if you use PNG/WEBP instead, update the filename in the blade to match.

NOTE: public/ is baked into the Docker image at build time, so after adding or
replacing the file you must rebuild and recreate the app container:

    docker compose build app
    docker compose up -d app
