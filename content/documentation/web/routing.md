+++
title = "Routing"
weight = 2
description = "Define routes, match a request method and path to a handler, add middleware, and generate URLs with phel.router"
aliases = ["/documentation/routing"]
+++

After this page you can map URLs and HTTP methods to handlers, read path parameters, add middleware, and build URLs from route names. The `phel.router` namespace turns a route table into one request-to-response function, so you do not write `cond` by hand.

{% php_note() %}
Like a Symfony or Laravel router, but routes are plain Phel data: a vector of `[path data]` pairs. No annotations, no config files. It builds on the Symfony routing component, which ships with Phel.
{% end %}

## Define routes and build the app

A route is `[path data]`, where `data` is a map. Put a handler under a method key (`:get`, `:post`, `:put`, `:patch`, `:delete`, `:head`, `:options`) to match that method. Put it under a top-level `:handler` to match any method. `router` builds a router from the table, and `handler` turns it into the function you call per request:

```phel
(ns my-app
  (:require phel.http :as http)
  (:require phel.router :as router))

(defn home [request]
  (http/html-response 200 "<h1>Home</h1>"))

(defn show-user [request]
  (let [id (get-in request [:attributes :match :path-params :id])]
    (http/response-from-map {:status 200 :body (str "User " id)})))

(def routes
  [["/" {:get {:handler home}}]
   ["/users/{id}" {:name :user
                   :get {:handler show-user}}]])

(def app (router/handler (router/router routes)))

(get (app (http/request-from-map {:method "GET" :uri "/users/42"})) :body)
; => "User 42"
(get (app (http/request-from-map {:method "POST" :uri "/users/42"})) :status)
; => 405
(get (app (http/request-from-map {:method "GET" :uri "/nope"})) :status)
; => 404
```

A handler takes a request and returns a response (see [Request and Response](/documentation/web/http-request-and-response/)). `app` is itself a handler, so the entry point stays the same: `(-> (http/request-from-globals) (app) (http/emit-response))`.

## Path parameters

`{id}` in a path matches one segment. The value arrives under `[:attributes :match :path-params]` as a string: `/users/42` gives `{:id "42"}`. Convert it with `parse-long` when you need a number:

```phel
(ns my-app
  (:require phel.http :as http)
  (:require phel.router :as router))

(defn next-user [request]
  (let [id (parse-long (get-in request [:attributes :match :path-params :id]))]
    (http/response-from-map {:status 200 :body (str (inc id))})))

(def app (router/handler (router/router [["/users/{id}" {:get {:handler next-user}}]])))

(get (app (http/request-from-map {:method "GET" :uri "/users/41"})) :body)
; => "42"
```

The route's data map is also on the request, under `[:attributes :route-data]`.

## Nested routes and middleware

Routes nest: a child adds its path to the parent's path and inherits the parent's data. A middleware is a function of two arguments, the next handler and the request. It can change the request, the response, or return early. Attach it with `:middleware` on a route (it applies to the route and its children), on a method map, or in the `handler` options (it applies to every route):

```phel
(ns my-app
  (:require phel.http :as http)
  (:require phel.router :as router))

(defn wrap-api-header [handler request]
  (assoc-in (handler request) [:headers :x-api] "1"))

(defn require-token [handler request]
  (if (= "secret" (get-in request [:query-params "token"]))
    (handler request)
    (http/response-from-map {:status 401 :body "Unauthorized"})))

(defn pong [request]
  (http/json-response 200 {:pong true}))

(defn admin [request]
  (http/response-from-map {:status 200 :body "admin"}))

(def routes
  [["/api" {:middleware [wrap-api-header]}
    ["/ping" {:get {:handler pong}}]
    ["/admin" {:middleware [require-token]
               :get {:handler admin}}]]])

(def app (router/handler (router/router routes)))

(get (app (http/request-from-map {:method "GET" :uri "/api/ping"})) :headers)
; => {:content-type "application/json", :x-api "1"}
(get (app (http/request-from-map {:method "GET" :uri "/api/admin"})) :status)
; => 401
```

## Error responses

The router answers with a plain response when it cannot dispatch. Override each case in the `handler` options:

| Option | When it runs | Default |
|---|---|---|
| `:not-found` | no route matches the path | 404 "Not found" |
| `:method-not-allowed` | the path matches, the method does not | 405 "Method not allowed" |
| `:not-acceptable` | the matched handler returns `nil` | 406 "Not acceptable" |
| `:default-handler` | any of the three above without its own option | |
| `:middleware` | every matched route | none |

```phel
(ns my-app
  (:require phel.http :as http)
  (:require phel.router :as router))

(def app
  (router/handler
    (router/router [["/" {:get {:handler (fn [_] (http/response-from-string "Home"))}}]])
    {:not-found (fn [_] (http/response-from-map {:status 404 :body "Nothing here"}))}))

(get (app (http/request-from-map {:method "GET" :uri "/nope"})) :body)
; => "Nothing here"
```

## Generate URLs

Give a route a `:name` to build its URL with `generate`, so links do not break when a path changes. `match-by-path` shows which route a path resolves to, without calling a handler:

```phel
(ns my-app
  (:require phel.router :as router))

(def r
  (router/router
    [["/users/{id}" {:name :user
                     :get {:handler (fn [_] {:status 200 :body "User"})}}]]))

(router/generate r :user {:id 42})
; => "/users/42"

(get (router/match-by-path r "/users/42") :path-params)
; => {:id "42"}
```

`match-by-name` returns the route's `:template` and `:data` for a name.

## Faster routing with compiled-router

`compiled-router` compiles the route table with Symfony's compiled matcher, about 3x faster for large tables. The compilation runs at macro-expansion time, so the routes must be a literal vector at the call site, not built from runtime values. Use `router` when routes are dynamic.

<!-- phel-test: skip -->
```phel
(router/handler
  (router/compiled-router
    [["/ping" {:get {:handler pong}}]]))
```

For every function and option, see the [router API reference](/documentation/reference/api/router/). Next, build the response body with [HTML Rendering](/documentation/web/html-rendering/).
