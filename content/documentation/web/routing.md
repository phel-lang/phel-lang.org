+++
title = "Routing"
weight = 2
description = "Define routes, match a request method and path to a handler, and dispatch to a response with phel.router"
aliases = ["/documentation/routing"]
+++

The `phel.router` namespace maps an incoming request to a handler so you do not write `cond` by hand. It builds a single `request -> response` function from a route table, on top of Symfony routing.

{% php_note() %}
Like a PHP framework router (Symfony, Laravel), but routes are plain Phel data: a vector of `[path data]` tuples. No annotations, no config files.
{% end %}

The router builds on the Symfony routing component, which ships with Phel, so `phel.router` is available out of the box.

## Define routes

A route is `[path data]`, where `data` is a map. Put a 1-arg `request -> response` function under `:handler` to match any method, or under a method key (`:get`, `:post`, ...) to match one method. Routes nest: children inherit the parent path prefix and merge the parent data.

```phel
(ns my-app
  (:require phel.http :as http)
  (:require phel.html :refer [html]))

(defn home [request]
  (http/response-from-map {:status 200 :body (html [:h1 "Home"])}))

(defn show-user [request]
  (let [id (get-in request [:attributes :match :id])]
    (http/response-from-map {:status 200 :body (html [:h1 (str "User " id)])})))

(def routes
  [["/" {:get {:handler home}}]
   ["/users/{id}" {:name :user :get {:handler show-user}}]])
```

A handler returns a response, built with `response-from-map` from [Request and Response](/documentation/web/http-request-and-response/). Path variables like `{id}` arrive under `[:attributes :match]` on the request.

## Build a router and a handler

`router` turns the route table into a `Router`. `handler` turns that router into the `request -> response` function you call per request.

```phel
(ns my-app
  (:require phel.router :as router))

(def app
  (router/handler (router/router routes)))
```

`handler` accepts options for the error cases:

```phel
(router/handler (router/router routes)
  {:not-found          (fn [_] {:status 404 :body "Not found"})
   :method-not-allowed (fn [_] {:status 405 :body "Method not allowed"})
   :middleware         [logging-mw]})
```

| option                | when it runs |
|-----------------------|--------------|
| `:not-found`          | no route matches the path (404) |
| `:method-not-allowed` | path matches but not the request method (405) |
| `:not-acceptable`     | a matched handler returns `nil` (406) |
| `:default-handler`    | fallback for any 404/405/406 not covered above |
| `:middleware`         | applied to every matched route |

## Match and dispatch

`match-by-path` tells you which route a path resolves to without invoking a handler. `match-by-name` and `generate` build URLs from a route `:name`.

```phel
(router/match-by-path (router/router routes) "/users/42")
;; the matched route data, including :path-params {:id 42}

(router/generate (router/router routes) :user {:id 42})
;; Evaluates to "/users/42"
```

Most of the time you skip these and let `handler` do the matching and dispatch in one call.

## The full web flow

Putting it together: read the request, let the router pick and run a handler, emit the response.

```phel
(ns my-app
  (:require phel.http :as http)
  (:require phel.router :as router))

(def app
  (router/handler (router/router routes)
    {:not-found (fn [_] {:status 404 :body "Not found"})}))

(-> (http/request-from-globals)
    (app)
    (http/emit-response))
```

The flow is: request (from [Request and Response](/documentation/web/http-request-and-response/)) -> route match -> handler -> response -> [HTML rendering](/documentation/web/html-rendering/) for the body -> emit.

## Faster routing with compiled-router

`compiled-router` precompiles the route table with Symfony's compiled matcher, around 3x faster for large tables. It runs at macro-expansion time, so the routes must be a literal vector at the call site, not built from runtime values. Use `router` when routes are dynamic.

```phel
(router/handler
  (router/compiled-router
    [["/ping" {:get {:handler pong}}]]))
```

For every function and its full signature, see the [router API reference](/documentation/reference/api/router/).

## Next steps

- [Request and Response](/documentation/web/http-request-and-response/) - the request and response structs handlers work with
- [HTML rendering](/documentation/web/html-rendering/) - build response bodies from Phel data
- [router API reference](/documentation/reference/api/router/) - every router function and option
