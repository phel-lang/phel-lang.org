+++
title = "Build a Web App"
weight = 1
description = "An end-to-end tutorial: build a guestbook web app in Phel from an empty folder, with routing, HTML rendering, and request handling."
+++

This tutorial builds a small, complete web app from an empty folder: a guestbook that lists messages and lets visitors post new ones. At the end you serve it with `php -S` and use it in the browser.

It uses four built-in namespaces: `phel.html` for the page, `phel.http` for requests and responses, `phel.router` for dispatch, and `phel.json` to store messages in a file.

You build the app in one file, `src/guestbook/app.phel`. Each step replaces the contents of that file with a complete program, and you run it with `vendor/bin/phel run src/guestbook/app.phel`. Steps 1 to 4 introduce one idea each. Step 5 is the final app. Step 6 serves it.

The trick that makes a web handler testable without a server is `request-from-map`: it builds a request in memory, so you can call your app and read the response in plain Phel.

## 0. Create the project

You need PHP 8.5+ and Composer (see [Installation](/documentation/installation/)). In an empty folder:

```bash
mkdir guestbook && cd guestbook
composer require phel-lang/phel-lang
vendor/bin/phel init guestbook
mkdir -p src/guestbook
```

`phel init` writes `phel-config.php` and a sample `src/main.phel`. You can ignore the sample. Create `src/guestbook/app.phel` for the next step.

## 1. Hold the state

The guestbook needs a place for messages. An `atom` holds a vector, and `swap!` updates it:

```phel
(ns guestbook.app)

(def messages (atom []))

(defn add-message! [name text]
  (swap! messages conj {:name name :text text}))

(add-message! "Ada" "First!")
(add-message! "Alan" "Hello from Phel")

(prn (deref messages))
; prints [{:name "Ada", :text "First!"} {:name "Alan", :text "Hello from Phel"}]
```

The atom lives only as long as one PHP process. A web server starts every request with fresh state, so under `php -S` the atom is empty again on the next request. Step 5 replaces it with a file.

## 2. Render the page

HTML is plain Phel data: a vector is an element, a leading keyword is the tag, and an optional map holds the attributes (see [HTML Rendering](/documentation/web/html-rendering/)). A function that returns such a vector is a reusable component. Pass the final tree to `html` once:

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

(prn (php/str_contains (page [{:name "Ada" :text "<b>Hi</b>"}]) "<strong>Ada</strong>: &lt;b&gt;Hi&lt;/b&gt;"))
; prints true
```

`html` escapes every value, so a message like `<b>Hi</b>` renders as text, not markup. There is no template language, only data.

## 3. Handle a request

A handler is a function from a request to a response. `home` renders the page. `sign` reads the submitted form from `:parsed-body`, stores it, and redirects back with a `303`. Form fields arrive as a map with string keys, named after the `<input>` elements:

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
  (prn [(get (sign req) :status) (deref messages)]))
; prints [303 [{:name "Ada", :text "Hi"}]]
```

The `303` status and the `Location` header make the browser load the list again after a post.

## 4. Route requests to handlers

`phel.router` maps a table of `[path data]` pairs to handlers and matches both path and method. `router/handler` turns the table into one request-to-response function, which is the whole app:

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

(app (http/request-from-map {:method "POST" :uri "/" :parsed-body {"name" "Ada" "message" "Hi"}}))
(prn (get (app (http/request-from-map {:method "GET" :uri "/"})) :body))
; prints "messages: 1"
(prn (get (app (http/request-from-map {:method "GET" :uri "/missing"})) :status))
; prints 404
```

The router answers unknown paths with a `404` without calling your code. `home` returns a short text here to keep the focus on routing. The next step renders the real page.

## 5. The complete app

Now assemble the pieces. One change: the atom goes. PHP forgets it when each request ends, so messages move to a JSON file that every request reads and writes. The file lives in the system temp directory, outside the web root, so nobody can download it.

Replace `src/guestbook/app.phel` with the full app:

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

; Quick check, replaced in step 6
(app (http/request-from-map {:method "POST" :uri "/" :parsed-body {"name" "Ada" "message" "Hello"}}))
(prn (php/str_contains (get (app (http/request-from-map {:method "GET" :uri "/"})) :body)
                       "<strong>Ada</strong>: Hello"))
; prints true
```

The check posts a message, renders the list, and confirms the HTML contains it. That proves the whole request-to-response path before a server is involved. It also writes to the messages file, so the message is still there on the next run.

## 6. Serve it

A web server needs a PHP entry file that boots Phel and loads your namespace. Create `public/index.php`:

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

\Phel::run(__DIR__ . '/..', 'guestbook.app');
```

In `src/guestbook/app.phel`, replace the quick check at the bottom with the entry point. It reads the request from PHP's globals, runs `app`, and sends the response:

<!-- phel-test: skip -->
```phel
(when-not *build-mode*
  (-> (http/request-from-globals)
      (app)
      (http/emit-response)))
```

The `*build-mode*` guard skips this code when `phel build` or `phel test` loads the file. From now on, run the app through the server, not with `phel run`: `request-from-globals` fails outside a web request.

Your project now has these files:

```text
composer.json
phel-config.php
public/index.php         # entry file for the web server
src/guestbook/app.phel   # the app
```

Start PHP's built-in server with `public` as the web root:

```bash
php -S 127.0.0.1:8000 -t public
```

Open `http://127.0.0.1:8000/` and sign the guestbook. Each request is a new PHP process, but the messages stay: they live in the JSON file, not in memory. Restart the server and they are still there.

## Where to go next

- **Use a database.** Two requests writing the file at the same time can lose a message. Replace `load-messages` and `add-message!` with SQL through [phel-pdo](https://github.com/phel-lang/phel-pdo) (see [Persistence](/documentation/web/framework-integration/#persistence-maps-not-entities)). The handlers stay the same.
- **Validate input.** Reject an empty name before `add-message!` and return a `400` with an error in the page. [Schema](/documentation/libraries/schema/) can describe the form.
- **Deploy it.** Compile ahead of time with `phel build` (see [Deployment](/documentation/guides/deployment/)).
