+++
title = "Build a Web App"
weight = 1
description = "An end-to-end tutorial: build a complete guestbook web app in Phel with routing, HTML rendering, and request handling. Every snippet runs."
+++

This tutorial builds a small but complete web app, a guestbook that lists
messages and lets visitors post new ones. It uses four built-in namespaces:
`phel.html` for the page, `phel.http` for requests and responses,
`phel.router` for dispatch, and `phel.json` to store messages in a file. You will
end with a single file you can serve with `php -S`.

Every code block here is a self-contained program you can paste into a file and
run with `phel run`, and each is checked against the runtime on every build.
Sections 1 to 4 are runnable steps that introduce one idea at a time. Section 5
assembles them into the final `src/guestbook/app.phel` you keep. The trick that makes
a web handler runnable without a server is `request-from-map`: it builds a request
struct in memory, so you can call your app and inspect the response in plain Phel,
no browser required.

## Prerequisites

Phel installed in a project (see [Getting Started](/documentation/getting-started/)).
Run each step below with `vendor/bin/phel run <file>.phel` to follow along. The
finished file lands in section 5.

## 1. Hold the state

The guestbook needs somewhere to keep messages. An `atom` holds a vector and
`swap!` updates it. Start there:

```phel
(ns guestbook.app)

(def messages (atom []))

(defn add-message! [name text]
  (swap! messages conj {:name name :text text}))

(add-message! "Ada" "First!")
(add-message! "Alan" "Hello from Phel")

(deref messages)
; => [{:name "Ada", :text "First!"} {:name "Alan", :text "Hello from Phel"}]
```

`messages` is the whole database for now. It lives only as long as one PHP
process. A web server starts every request with fresh state, so under `php -S`
the atom is empty again on the next request. Section 5 swaps it for a file.

## 2. Render the page

HTML is plain Phel data: a vector is an element, a leading keyword is the tag,
an optional map is attributes (see [HTML Rendering](/documentation/web/html-rendering/)).
A function that returns such a vector is a reusable component. Pass the final
tree to `html` once:

```phel
(ns guestbook.app
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
; => true
```

`html` auto-escapes every value, so a message of `<script>` renders as harmless
text. No template language. Only data.

## 3. Handle a request

A handler is a one-argument function `request -> response`. `home` renders the
page. `sign` reads the submitted form from `:parsed-body`, stores it, and
redirects back with a `303`. Form fields arrive as a map with string keys, the
same names as the `<input>` elements. Build requests with `request-from-map` to
call the handlers directly:

```phel
(ns guestbook.app
  (:require phel.http :as http))

(def messages (atom []))

(defn home [request]
  (http/response-from-map {:status 200 :body "the page"}))

(defn sign [request]
  (let [body (get request :parsed-body)]
    (swap! messages conj {:name (get body "name") :text (get body "message")})
    (http/response-from-map {:status 303 :headers {"Location" "/"} :body ""})))

(let [req (http/request-from-map
            {:method "POST" :uri "/" :parsed-body {"name" "Ada" "message" "Hi"}})]
  [(get (sign req) :status) (deref messages)])
; => [303 [{:name "Ada", :text "Hi"}]]
```

The handler returns a response struct; `sign` sets `:status 303` and a
`Location` header so the browser reloads the list after posting.

## 4. Route requests to handlers

`phel.router` maps a `[path data]` table to handlers, matching both path and
method, so you skip hand-written `cond`. `router/handler` turns the table into
one `request -> response` function, the whole app:

```phel
(ns guestbook.app
  (:require phel.http :as http)
  (:require phel.router :as router))

(def messages (atom []))

(defn home [request]
  (http/response-from-map {:status 200 :body (str "messages: " (count (deref messages)))}))

(defn sign [request]
  (let [body (get request :parsed-body)]
    (swap! messages conj {:name (get body "name") :text (get body "message")})
    (http/response-from-map {:status 303 :headers {"Location" "/"} :body ""})))

(def routes
  [["/" {:get {:handler home}
         :post {:handler sign}}]])

(def app (router/handler (router/router routes)))

; POST a message, then GET the list, all in memory
(let [post-req (http/request-from-map {:method "POST" :uri "/" :parsed-body {"name" "Ada" "message" "Hi"}})
      get-req  (http/request-from-map {:method "GET" :uri "/"})]
  (app post-req)
  (get-in (app get-req) [:body]))
; => "messages: 1"
```

`app` is everything: routing, dispatch, your handlers. A `GET /missing` would
get a 404 from the router without touching your code. Here `home` returns a stub
body to keep the focus on routing; the complete app below renders the real page.

## 5. The complete app

Assemble the pieces into one `src/guestbook/app.phel`. One change first: the
atom goes. PHP starts every request with fresh state, so an atom forgets each
message as soon as the request that added it ends. The messages move to a JSON
file that every request reads and writes.

`load-messages` reads the file, `add-message!` appends to it. The file lives in
the system temp directory, outside the web root, so nobody can download it. This
is the whole app: storage, the `page` component from section 2, the real `home`
(which now renders `(page ...)`), `sign`, the routes, and `app`. It is a complete
program, copy it as is:

```phel
(ns guestbook.app
  (:require phel.html :refer [html doctype])
  (:require phel.http :as http)
  (:require phel.json :as json)
  (:require phel.router :as router))

(def messages-file (str (php/sys_get_temp_dir) "/guestbook-messages.json"))

(defn load-messages []
  (if (php/is_file messages-file)
    (json/decode (php/file_get_contents messages-file))
    []))

(defn add-message! [name text]
  (php/file_put_contents
    messages-file
    (json/encode (conj (load-messages) {:name name :text text}))))

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
  (http/response-from-map {:status 200 :body (page (load-messages))}))

(defn sign [request]
  (let [body (get request :parsed-body)]
    (add-message! (get body "name") (get body "message"))
    (http/response-from-map {:status 303 :headers {"Location" "/"} :body ""})))

(def routes
  [["/" {:get {:handler home}
         :post {:handler sign}}]])

(def app (router/handler (router/router routes)))

; Quick check (delete before serving): post a message, render the list,
; confirm the rendered page shows it.
(let [post (http/request-from-map {:method "POST" :uri "/" :parsed-body {"name" "Ada" "message" "Hello"}})
      _    (app post)
      body (get (app (http/request-from-map {:method "GET" :uri "/"})) :body)]
  (php/str_contains body "<strong>Ada</strong>: Hello"))
; => true
```

The check posts a message and confirms the rendered HTML contains it. That
proves the whole request to response path before any server is involved. It
also writes to the messages file, which is the point: the next request, or the
next `phel run`, sees the message.

## 6. Serve it

A Phel web app is served through a tiny PHP front controller that boots Phel and
runs your namespace. The project layout is three files:

```text
composer.json            # requires phel-lang/phel-lang
public/index.php         # front controller
src/guestbook/app.phel   # the app from section 5
```

`public/index.php` boots Phel and runs the `guestbook.app` namespace:

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

\Phel::run(__DIR__ . '/..', 'guestbook.app');
```

Delete the quick check at the bottom of `src/guestbook/app.phel`, then add the
entry point in its place: read the request from PHP's globals, run `app`, emit
the response. Guard it with `(when-not *build-mode* ...)` so it only runs when
serving, not when the file is compiled or required by tests:

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

Open `http://127.0.0.1:8000/`, sign the guestbook, watch the list grow. Each
request is a new PHP process, but the messages survive: they live in the JSON
file, not in memory. Restart the server and they are still there.

## Where to go next

- **Use a database.** The file works for one visitor at a time. Two requests
  writing at once can lose a message. Swap `load-messages` and `add-message!`
  for SQL with [phel-pdo](https://github.com/phel-lang/phel-pdo) (see
  [Persistence](/documentation/web/framework-integration/#persistence-maps-not-entities)).
  The handlers do not change.
- **Validate input.** Reject empty names before `add-message!`, return a `400`
  with an error message in the page.
- **Add pages.** A second route `["/about" {:get {:handler about}}]` and a
  shared `layout` component (see [HTML Rendering](/documentation/web/html-rendering/#composing-reusable-fragments)).

Reference: [Routing](/documentation/web/routing/),
[Request and Response](/documentation/web/http-request-and-response/),
[HTML Rendering](/documentation/web/html-rendering/).
