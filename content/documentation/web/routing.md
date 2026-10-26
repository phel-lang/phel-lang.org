+++
title = "Routing"
weight = 2
description = "Define routes, match a request method and path to a handler, add middleware, and generate URLs with phel.router"
aliases = ["/documentation/routing"]
+++

`phel.router` turns a route table into one request-to-response function. Routes are plain Phel data, a vector of `[path data]` pairs, and matching uses the Symfony routing component, which ships with Phel.

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

`app` is itself a [handler](/documentation/web/http-request-and-response/), so the entry point stays the same: `(-> (http/request-from-globals) (app) (http/emit-response))`.

## Path parameters

`{id}` in a path matches one segment. The value arrives under `[:attributes :match :path-params]` as a string, as in `show-user` above; convert it with `parse-long` when you need a number. The route's data map is on the request under `[:attributes :route-data]`.

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

When it cannot dispatch, the router answers 404 (no route matches), 405 (the path matches, the method does not), or 406 (the handler returned `nil`). Override them in the `handler` options with `:not-found`, `:method-not-allowed`, `:not-acceptable`, or one `:default-handler` for all three:

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

For large tables, `compiled-router` is a drop-in for `router` and about 3x faster. It compiles at macro-expansion time, so the routes must be a literal vector at the call site.

For every function and option, see the [router API reference](/documentation/reference/api/router/).
