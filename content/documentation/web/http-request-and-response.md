+++
title = "Request and Response"
weight = 1
aliases = ["/documentation/http-request-and-response"]
+++

## HTTP request

PHP scatters the request across `$_GET`, `$_POST`, `$_SERVER`, `$_COOKIES`, `$_FILES`. Phel normalizes them into one struct. All in `phel\http`.

Request struct:

```phel
(defstruct request [
  method            ; HTTP Method ("GET", "POST", ...)
  uri               ; the 'uri' struct (see below)
  headers           ; Map of all headers. Keys are keywords, Values are string
  parsed-body       ; The parsed body ($_POST), when availabe otherwise nil
  query-params      ; Map with all query parameters ($_GET)
  cookie-params     ; Map with all cookie parameters ($_COOKIE)
  server-params     ; Map with all server parameters ($_SERVER)
  uploaded-files    ; Map of 'uploaded-file' structs (see below)
  version           ; The HTTP Version
  attributes        ; consumer specific data to enrich the request
])

(defstruct uri [
  scheme            ; Scheme of the URI ("http", "https")
  userinfo          ; User info string
  host              ; Hostname of the URI
  port              ; Port of the URI
  path              ; Path of the URI
  query             ; Query string of the URI
  fragment          ; Fragement string of the URI
])

(defstruct uploaded-file [
  tmp-file          ; The location of the temporary file
  size              ; The file size
  error-status      ; The upload error status
  client-filename   ; The client filename
  client-media-type ; The client media type
])
```

Import `phel\http`, call `request-from-globals`:

```phel
(ns my-namepace
  (:require phel\http))

(http/request-from-globals) ; Evaluates to a request struct
```

## HTTP response

`phel\http` includes a response struct for sending responses:

```phel
(defstruct response [
  status    ; The HTTP status code
  headers   ; A map of headers
  body      ; The body of the response (string)
  version   ; The HTTP protocol version
  reason    ; The HTTP status code reason text
])
```

Two helpers create responses:

```phel
(ns my-namepace
  (:require phel\http))

;; Create response from map
(http/response-from-map {:status 200 :body "Test"})
;; Evaluates to (response 200 {} "Test" "1.1" "OK")

;; Create response from string
(http/response-from-string "Hello World")
;; Evaluates to (response 200 {} "Hello World" "1.1" "OK")
```

Send with `emit-response`:

```phel
(ns my-namepace
  (:require phel\http))

(let [rsp (http/response-from-map
            {:status 404 :body "Page not found"})]
  (http/emit-response rsp))
```

## HTTP router

Phel router on top of Symfony routing: [phel-lang/router](https://github.com/phel-lang/router).
