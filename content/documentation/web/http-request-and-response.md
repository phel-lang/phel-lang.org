+++
title = "Request and Response"
weight = 1
description = "Read an HTTP request, build and emit a response, and test handlers in memory with the phel.http namespace"
aliases = ["/documentation/http-request-and-response"]
+++

The `phel.http` namespace turns a PHP web request into Phel data and turns your response back into output. This page shows how to read a request, build and send a response, and test a handler without a web server.

## The request-response cycle

Everything lives in `phel.http`. A web entry point reads the request from PHP's globals, turns it into a response, and sends it with `emit-response` (once, at the end of the request):

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

`handle` is a plain function from a request to a response. `request-from-globals` works only inside a web request (PHP-FPM, mod_php, or `php -S`); from the REPL or a test, use `request-from-map` (see [Test without a server](#test-without-a-server)). For more than a few paths, use the [router](/documentation/web/routing/).

## Read the request

`request-from-globals` collects `$_SERVER`, `$_GET`, `$_POST`, `$_COOKIE`, and `$_FILES` into one struct. Read its fields with `get` and `get-in`:

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

A response is a struct with `:status`, `:headers`, `:body`, `:version`, and `:reason`. `response-from-map` builds any response, and `response-from-string`, `html-response`, and `json-response` cover the common cases:

```phel
(ns my-app
  (:require phel.http :as http))

(http/response-from-map {:status 201 :headers {:location "/users/1"} :body ""})
; => (phel.http.response 201 {:location "/users/1"} "" "1.1" "Created")

(http/json-response 200 {:message "pong"})
; => (phel.http.response 200 {:content-type "application/json"} "{\"message\":\"pong\"}" "1.1" "OK")
```

`html-response` and `json-response` set the `Content-Type` header; `json-response` also encodes the body. For the HTML body, see [HTML Rendering](/documentation/web/html-rendering/).

## Test without a server

`request-from-map` takes the keys from the table above; `:uri` can be a string and missing keys get empty defaults. Call the `handle` function from the first example and check the response:

<!-- phel-test: skip -->
```phel
(get (handle (http/request-from-map {:method "GET" :uri "/"})) :status)
; => 200
(get (handle (http/request-from-map {:method "GET" :uri "/missing"})) :status)
; => 404
```

`request-from-map` does not parse the query string into `:query-params`. Pass `:query-params` yourself when the handler reads it. For assertions in a test suite, see [Testing](/documentation/guides/testing/).

For every function in the namespace, see the [http API reference](/documentation/reference/api/http/).
