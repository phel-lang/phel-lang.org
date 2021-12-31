# Phel website

This is the code for the website of Phel: https://phel-lang.org.

## Build the documentation

The documentation is build with [Zola](https://www.getzola.org/).

```bash
zola serve # build & serve
zola build # build & publish
```

## Build the API page

```bash
composer install # install phel as composer dependency
php build/api-page.php > content/documentation/api.md
```
