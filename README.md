# Phel website

This is the code for the website of Phel: https://phel-lang.org.

## How to

1. Build the API page
2. Build the documentation

### Build the API page

```bash
# install phel as a composer dependency
composer install

# build the API doc page, API search index & releases pages
composer build
```

### Build the documentation

The documentation is build with [Zola](https://www.getzola.org/).

#### Local development

```bash
npm run dev # serve and watch CSS changes
```

#### Prod environment

```bash
zola build # build & publish
```
