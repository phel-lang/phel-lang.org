+++
title = "Build a Web App"
weight = 1
description = "An end-to-end tutorial: build a complete guestbook web app in Phel with routing, HTML rendering, and request handling. Every snippet runs."
+++

This tutorial builds a small but complete web app, a guestbook that lists
messages and lets visitors post new ones, using three built-in namespaces:
`phel.html` for the page, `phel.http` for requests and responses, and
`phel.router` for dispatch. You will end with a single file you can serve with
`php -S`.

Every code block here is a self-contained program you can paste into a file and
run with `phel run`, and each is checked against the runtime on every build.
Sections 1 to 4 are runnable steps that introduce one idea at a time; section 5
assembles them into the final `src/guestbook.phel` you keep. The trick that makes
a web handler runnable without a server is `request-from-map`: it builds a request
struct in memory, so you can call your app and inspect the response in plain Phel,
no browser required.

## Prerequisites

Phel installed in a project (see [Getting Started](/documentation/getting-started/)).
Run each step below with `vendor/bin/phel run <file>.phel` to follow along; the
finished file lands in section 5.

## 1. Hold the state

The guestbook needs somewhere to keep messages. An `atom` holds a vector and
`swap!` updates it. Start there:

```phel
(ns guestbook)

(def messages (atom []))

(defn add-message! [name text]
  (swap! messages conj {:name name :text text}))

(add-message! "Ada" "First!")
(add-message! "Alan" "Hello from Phel")

(deref messages)
# => [{:name "Ada", :text "First!"} {:name "Alan", :text "Hello from Phel"}]
```

`messages` is the whole database for now. Section 5 swaps it for a file.

## 2. Render the page

HTML is plain Phel data: a vector is an element, a leading keyword is the tag,
an optional map is attributes (see [HTML Rendering](/documentation/web/html-rendering/)).
A function that returns such a vector is a reusable component. Pass the final
tree to `html` once:

```phel
(ns guestbook
  (:require phel.html :refer [html doctype]))

(defn entry-view [entry]
  [:li [:strong (get entry :name)] ": " (get entry :text)])

(defn page [entries]
  (html
    (doctype :html5)
    [:html
     [:head [:title "Guestbook"]]
     [:body
      [:h1 "Guestbook"]
      [:ul (for [m :in entries] (entry-view m))]
      [:form {:method "post" :action "/"}
       [:input {:type "text" :name "name" :placeholder "Your name"}]
       [:input {:type "text" :name "message" :placeholder "Message"}]
       [:button "Sign"]]]]))

(php/str_contains (page [{:name "Ada" :text "Hi"}]) "<strong>Ada</strong>")
# => true
```

`html` auto-escapes every value, so a message of `<script>` renders as harmless
text. No template language, just data.

## 3. Handle a request

A handler is a one-argument function `request -> response`. `home` renders the
page; `sign` reads the submitted form from `:parsed-body`, stores it, and
redirects back with a `303`. Build requests with `request-from-map` to call them
directly:

```phel
(ns guestbook
  (:require phel.http :as http))

(def messages (atom []))

(defn home [request]
  (http/response-from-map {:status 200 :body "the page"}))

(defn sign [request]
  (let [body (get request :parsed-body)]
    (swap! messages conj {:name (get body :name) :text (get body :message)})
    (http/response-from-map {:status 303 :headers {"Location" "/"} :body ""})))

(let [req (http/request-from-map
            {:method "POST" :uri "/" :parsed-body {:name "Ada" :message "Hi"}})]
  [(get (sign req) :status) (deref messages)])
# => [303 [{:name "Ada", :text "Hi"}]]
```

The handler returns a response struct; `sign` sets `:status 303` and a
`Location` header so the browser reloads the list after posting.

## 4. Route requests to handlers

`phel.router` maps a `[path data]` table to handlers, matching both path and
method, so you skip hand-written `cond`. `router/handler` turns the table into
one `request -> response` function, the whole app:

```phel
(ns guestbook
  (:require phel.http :as http)
  (:require phel.router :as router))

(def messages (atom []))

(defn home [request]
  (http/response-from-map {:status 200 :body (str "messages: " (count (deref messages)))}))

(defn sign [request]
  (let [body (get request :parsed-body)]
    (swap! messages conj {:name (get body :name) :text (get body :message)})
    (http/response-from-map {:status 303 :headers {"Location" "/"} :body ""})))

(def routes
  [["/" {:get {:handler home}
         :post {:handler sign}}]])

(def app (router/handler (router/router routes)))

# POST a message, then GET the list, all in memory
(let [post-req (http/request-from-map {:method "POST" :uri "/" :parsed-body {:name "Ada" :message "Hi"}})
      get-req  (http/request-from-map {:method "GET" :uri "/"})]
  (app post-req)
  (get-in (app get-req) [:body]))
# => "messages: 1"
```

`app` is everything: routing, dispatch, your handlers. A `GET /missing` would
get a 404 from the router without touching your code. Here `home` returns a stub
body to keep the focus on routing; the complete app below renders the real page.

## 5. The complete app

Assemble the pieces into one `src/guestbook.phel`. This is the whole app: state,
the `page` component from section 2, the real `home` (which now renders
`(page ...)`), `sign`, the routes, and `app`. It is a complete program, copy it
as is:

```phel
(ns guestbook
  (:require phel.html :refer [html doctype])
  (:require phel.http :as http)
  (:require phel.router :as router))

(def messages (atom []))

(defn add-message! [name text]
  (swap! messages conj {:name name :text text}))

(defn entry-view [entry]
  [:li [:strong (get entry :name)] ": " (get entry :text)])

(defn page [entries]
  (html
    (doctype :html5)
    [:html
     [:head [:title "Guestbook"]]
     [:body
      [:h1 "Guestbook"]
      [:ul (for [m :in entries] (entry-view m))]
      [:form {:method "post" :action "/"}
       [:input {:type "text" :name "name" :placeholder "Your name"}]
       [:input {:type "text" :name "message" :placeholder "Message"}]
       [:button "Sign"]]]]))

(defn home [request]
  (http/response-from-map {:status 200 :body (page (deref messages))}))

(defn sign [request]
  (let [body (get request :parsed-body)]
    (add-message! (get body :name) (get body :message))
    (http/response-from-map {:status 303 :headers {"Location" "/"} :body ""})))

(def routes
  [["/" {:get {:handler home}
         :post {:handler sign}}]])

(def app (router/handler (router/router routes)))

# Quick in-memory check (delete before serving): post a message, render the
# list, confirm the rendered page actually shows it.
(let [post (http/request-from-map {:method "POST" :uri "/" :parsed-body {:name "Ada" :message "Hello"}})
      _    (app post)
      body (get (app (http/request-from-map {:method "GET" :uri "/"})) :body)]
  (php/str_contains body "<strong>Ada</strong>: Hello"))
# => true
```

The check posts a message and confirms the rendered HTML contains it, proving the
whole request to response path before any server is involved.

## 6. Serve it

A Phel web app is served through a tiny PHP front controller that boots Phel and
runs your namespace. The project layout is three files:

```text
composer.json          # requires phel-lang/phel-lang
public/index.php       # front controller
src/guestbook.phel     # the app from section 5
```

`public/index.php` boots Phel and runs the `guestbook` namespace:

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

\Phel::run(__DIR__ . '/..', 'guestbook');
```

Then add the entry point at the bottom of `src/guestbook.phel`: read the request
from PHP's globals, run `app`, emit the response. Guard it with
`(when-not *build-mode* ...)` so it only runs when serving, not when the file is
compiled or required by tests:

<!-- phel-test: skip -->
```phel
(when-not *build-mode*
  (-> (http/request-from-globals)
      (app)
      (http/emit-response)))
```

Install dependencies and start PHP's built-in server with `public` as the web
root:

```bash
composer install
php -S 127.0.0.1:8000 -t public
```

Open `http://127.0.0.1:8000/`, sign the guestbook, watch the list grow. (Messages
live in the atom, so they reset when the server restarts: the first item under
**Where to go next** fixes that.)

## Where to go next

- **Persist to disk.** Swap the atom for a file: read messages with
  [`phel.json`](/documentation/reference/api/json/) on start, write on each
  `sign`. The handler code does not change, only `messages`.
- **Validate input.** Reject empty names before `swap!`, return a `400` with an
  error message in the page.
- **Add pages.** A second route `["/about" {:get {:handler about}}]` and a
  shared `layout` component (see [HTML Rendering](/documentation/web/html-rendering/#composing-reusable-fragments)).

Reference: [Routing](/documentation/web/routing/),
[Request and Response](/documentation/web/http-request-and-response/),
[HTML Rendering](/documentation/web/html-rendering/).
