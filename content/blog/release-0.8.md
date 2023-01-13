+++
title = "Release: v0.8.0"
date = "2023-01-15"
+++

This release adds a new phel\json library.

## phel\json

You can use the `phel\json` library to encode or decode a json directly from phel.

### Encode

You can encode a phel structure to json using the json/encode function.

```phel
(json/encode [1 2 3]) # "[1,2,3]"
(json/encode {:key1 1 :key2 "value2"} # "{\"key1\":1,\"key2\":\"value2\"}"
```

### Decode

You can decode a json string to a phel structure using the json/decode function.

Considering `your.json` as:
```json
{
  "name": "John Doe",
  "age": 30,
  "image": "",
  "email": "john@example.com",
  "phone": "(912) 555-4321",
  "url": "https://example.com",
  "location": {
    "address": "2712 Broadway St",
    "postalCode": "CA 94115",
    "countryCode": "US"
  }
}
```

```phel
(def your-json (php/file_get_contents (str __DIR__ "/your.json")))
(json/decode your-json) # equals to:
{
  :name "John Doe"
  :age 30
  :image ""
  :email "john@example.com"
  :phone "(912) 555-4321"
  :url "https://example.com"
  :location {
    :address "2712 Broadway St"
    :postalCode "CA 94115"
    :countryCode "US"
  }
}
```

## Other improvements

- Allow strings on `empty?`
- Improved error message when using strings on `count`
- Added `contains-value?` function

## Full list

For a full list of changes have a look at the [Changelog](https://github.com/phel-lang/phel-lang/blob/master/CHANGELOG.md).

