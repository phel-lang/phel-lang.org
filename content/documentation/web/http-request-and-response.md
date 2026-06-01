+++
title = "Request and Response"
weight = 1
description = "Read an HTTP request from PHP globals and build, then emit, a response with the phel.http namespace"
aliases = ["/documentation/http-request-and-response"]
+++

The `phel.http` namespace gives you one struct for the incoming request and one for the response you send back. This page shows how to read a request, build a response, and emit it.

## HTTP request

PHP scatters the request across `$_GET`, `$_POST`, `$_SERVER`, `$_COOKIES`, `$_FILES`. Phel normalizes them into one struct. All in `phel.http`.

Request struct:

```phel
(defstruct request [
  method            ; HTTP Method ("GET", "POST", ...)
  uri               ; uri struct (defined below)
  headers           ; Map of all headers. Keys are keywords, Values are string
  parsed-body       ; The parsed body ($_POST), when available otherwise nil
  query-params      ; Map with all query parameters ($_GET)
  cookie-params     ; Map with all cookie parameters ($_COOKIE)
  server-params     ; Map with all server parameters ($_SERVER)
  uploaded-files    ; map of uploaded-file structs (defined below)
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
  fragment          ; Fragment string of the URI
])

(defstruct uploaded-file [
  tmp-file          ; The location of the temporary file
  size              ; The file size
  error-status      ; The upload error status
  client-filename   ; The client filename
  client-media-type ; The client media type
])
```

Import `phel.http`, call `request-from-globals`:

<!-- phel-test: skip -->
```phel
(ns my-namespace
  (:require phel.http :as http))

(http/request-from-globals) ; Evaluates to a request struct
```

## HTTP response

`phel.http` includes a response struct for sending responses:

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
(ns my-namespace
  (:require phel.http))

;; Create response from map
(http/response-from-map {:status 200 :body "Test"})
;; Evaluates to (phel\http\response 200 {} Test 1.1 OK)

;; Create response from string
(http/response-from-string "Hello World")
;; Evaluates to (phel\http\response 200 {} Hello World 1.1 OK)
```

Send with `emit-response`:

```phel
(ns my-namespace
  (:require phel.http))

(let [rsp (http/response-from-map
            {:status 404 :body "Page not found"})]
  (http/emit-response rsp))
```

## End to end

A minimal web entry point reads the request, branches on method and path, builds a response, and emits it. Here `get-in` reads the path from the nested `uri` struct.

<!-- phel-test: skip -->
```phel
(ns my-app
  (:require phel.http :as http)
  (:require phel.html :refer [html]))

(defn handle [request]
  (let [method (get request :method)
        path   (get-in request [:uri :path])]
    (cond
      (and (= method "GET") (= path "/"))
      (http/response-from-map {:status 200 :body (html [:h1 "Home"])})

      (http/response-from-map {:status 404 :body "Not found"}))))

;; Wire it up: read globals, handle, emit.
(-> (http/request-from-globals)
    (handle)
    (http/emit-response))
```

Branching by hand stays readable for a few routes. Once you have more than a handful, reach for the [router](/documentation/web/routing/), which matches method and path for you and keeps handlers separate. The `:body` here comes from [HTML rendering](/documentation/web/html-rendering/).

## Next steps

- [Routing](/documentation/web/routing/) - match a request to a handler without hand-written `cond`
- [HTML rendering](/documentation/web/html-rendering/) - build the response body from Phel data structures
- [http API reference](/documentation/reference/api/http/) - every request and response helper
