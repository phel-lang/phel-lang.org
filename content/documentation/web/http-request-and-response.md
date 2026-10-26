+++
title = "Request and Response"
weight = 1
description = "Read an HTTP request, build and emit a response, and test handlers in memory with the phel.http namespace"
aliases = ["/documentation/http-request-and-response"]
+++

After this page you can read an incoming HTTP request, build a response, send it, and test a handler without a web server. Everything lives in `phel.http`.

## The request-response cycle

A web entry point does three things: read the request from PHP's globals, turn it into a response, and send the response:

<!-- phel-test: skip -->
```phel
(ns my-app
  (:require phel.http :as http))

(defn handle [request]
  (if (= "/" (get-in request [:uri :path]))
    (http/html-response 200 "<h1>Home</h1>")
    (http/response-from-map {:status 404 :body "Not found"})))

(-> (http/request-from-globals)
    (handle)
    (http/emit-response))
```

`handle` is a plain function from a request to a response. `request-from-globals` works only inside a web request (PHP-FPM, mod_php, or `php -S`). From the REPL or a test, build the request with `request-from-map` instead (see [Test without a server](#test-without-a-server)).

Branching by hand is fine for two or three paths. For more, use the [router](/documentation/web/routing/).

## Read the request

PHP spreads a request over `$_SERVER`, `$_GET`, `$_POST`, `$_COOKIE`, and `$_FILES`. `request-from-globals` collects them into one struct. Read its fields with `get` and `get-in`:

| Field | Value |
|---|---|
| `:method` | `"GET"`, `"POST"`, ... |
| `:uri` | a `uri` struct: `:scheme`, `:userinfo`, `:host`, `:port`, `:path`, `:query`, `:fragment` |
| `:headers` | map of headers, keys are lowercase keywords (`:content-type`) |
| `:parsed-body` | form data from `$_POST`, or a decoded JSON body, otherwise `nil` |
| `:query-params` | map of query parameters (`$_GET`) |
| `:cookie-params` | map of cookies (`$_COOKIE`) |
| `:server-params` | map of server values (`$_SERVER`) |
| `:uploaded-files` | `uploaded-file` structs: `:tmp-file`, `:size`, `:error-status`, `:client-filename`, `:client-media-type` |
| `:version` | HTTP version, such as `"1.1"` |
| `:attributes` | map for your own data; the router stores its match here |

Form fields and query parameters keep the string keys PHP gives them:

```phel
(ns my-app
  (:require phel.http :as http))

(def request
  (http/request-from-map
    {:method "POST"
     :uri "https://example.com/users?page=2"
     :query-params {"page" "2"}
     :parsed-body {"name" "Ada"}}))

(get request :method)             ; => "POST"
(get-in request [:uri :host])     ; => "example.com"
(get-in request [:uri :path])     ; => "/users"
(get-in request [:query-params "page"]) ; => "2"
(get-in request [:parsed-body "name"])  ; => "Ada"
```

## Build a response

A response is a struct with `:status`, `:headers`, `:body`, `:version`, and `:reason`. Four helpers build one:

```phel
(ns my-app
  (:require phel.http :as http))

(http/response-from-map {:status 201 :headers {:location "/users/1"} :body ""})
; => (phel.http.response 201 {:location "/users/1"} "" "1.1" "Created")

(http/response-from-string "Hello World")
; => (phel.http.response 200 {} "Hello World" "1.1" "OK")

(http/html-response 200 "<h1>Hi</h1>")
; => (phel.http.response 200 {:content-type "text/html; charset=utf-8"} "<h1>Hi</h1>" "1.1" "OK")

(http/json-response 200 {:message "pong"})
; => (phel.http.response 200 {:content-type "application/json"} "{\"message\":\"pong\"}" "1.1" "OK")
```

`html-response` and `json-response` set the `Content-Type` header. `json-response` also encodes the body. For the HTML body itself, see [HTML Rendering](/documentation/web/html-rendering/).

## Send the response

`emit-response` writes the status line, the headers, and the body to the client:

<!-- phel-test: skip -->
```phel
(http/emit-response (http/response-from-map {:status 404 :body "Page not found"}))
```

Call it once, at the end of the request.

## Test without a server

`request-from-map` builds a request from a map. It accepts the same keys as the table above, and `:uri` can be a string. Missing keys get empty defaults. With it, you can call any handler and check the response in plain Phel:

```phel
(ns my-app
  (:require phel.http :as http))

(defn handle [request]
  (if (= "/" (get-in request [:uri :path]))
    (http/html-response 200 "<h1>Home</h1>")
    (http/response-from-map {:status 404 :body "Not found"})))

(get (handle (http/request-from-map {:method "GET" :uri "/"})) :status)
; => 200
(get (handle (http/request-from-map {:method "GET" :uri "/missing"})) :status)
; => 404
```

`request-from-map` does not parse the query string into `:query-params`. Pass `:query-params` yourself when the handler reads it. For assertions in a test suite, see [Testing](/documentation/guides/testing/).

For every function in the namespace, see the [http API reference](/documentation/reference/api/http/).
