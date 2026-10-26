+++
title = "Build a Web App"
weight = 1
description = "An end-to-end tutorial: build a guestbook web app in Phel from an empty folder, with routing, HTML rendering, and request handling."
+++

This tutorial builds a guestbook from an empty folder: a page that lists messages and lets visitors post new ones. It uses `phel.html` for the page, `phel.http` for requests and responses, `phel.router` for dispatch, and `phel.json` to store messages in a file. At the end you serve it with `php -S`.

## 0. Create the project

You need PHP 8.5+ and Composer (see [Installation](/documentation/installation/)). In an empty folder:

```bash
mkdir guestbook && cd guestbook
composer require phel-lang/phel-lang
vendor/bin/phel init guestbook
mkdir -p src/guestbook
```

`phel init` writes `phel-config.php` and a sample `src/main.phel`, which you can ignore. The whole app lives in `src/guestbook/app.phel`.

## 1. Store messages and render the page

PHP starts every web request with fresh memory, so an `atom` would forget messages between requests. The guestbook stores them in a JSON file in the system temp directory, outside the web root, so nobody can download it.

HTML is plain Phel data: a vector is an element, a leading keyword is the tag, and an optional map holds the attributes (see [HTML Rendering](/documentation/web/html-rendering/)). A function that returns such a vector is a reusable component. Pass the final tree to `html` once.

Create `src/guestbook/app.phel`:

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

(prn (php/str_contains (page [{:name "Ada" :text "<b>Hi</b>"}]) "<strong>Ada</strong>: &lt;b&gt;Hi&lt;/b&gt;"))
; prints true
```

Run it with `vendor/bin/phel run src/guestbook/app.phel`. `html` escapes every value, so a message like `<b>Hi</b>` renders as text, not markup.

## 2. Handle requests

A handler is a function from a request to a response. `home` renders the page. `sign` reads the submitted form from `:parsed-body` (a map with string keys, named after the `<input>` elements), stores it, and redirects back with a `303`, so the browser loads the list again. `phel.router` matches path and method, answers unknown paths with a `404`, and `router/handler` turns the table into one function, which is the whole app.

Replace the `prn` check at the bottom of the file with:

<!-- phel-test: skip -->
```phel
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

; Quick check, replaced in step 3
(app (http/request-from-map {:method "POST" :uri "/" :parsed-body {"name" "Ada" "message" "Hello"}}))
(prn (php/str_contains (get (app (http/request-from-map {:method "GET" :uri "/"})) :body)
                       "<strong>Ada</strong>: Hello"))
; prints true
```

`request-from-map` builds a request in memory, so the check tests the whole request-to-response path without a server. It also writes to the messages file, so the message is still there on the next run.

## 3. Serve it

A web server needs a PHP entry file that boots Phel and loads your namespace. Create `public/index.php`:

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

\Phel::run(__DIR__ . '/..', 'guestbook.app');
```

In `src/guestbook/app.phel`, replace the quick check with the entry point. It reads the request from PHP's globals, runs `app`, and sends the response:

<!-- phel-test: skip -->
```phel
(when-not *build-mode*
  (-> (http/request-from-globals)
      (app)
      (http/emit-response)))
```

The `*build-mode*` guard skips this code when `phel build` or `phel test` loads the file. From now on, run the app through the server, not with `phel run`: `request-from-globals` fails outside a web request.

Start PHP's built-in server with `public` as the web root:

```bash
php -S 127.0.0.1:8000 -t public
```

Open `http://127.0.0.1:8000/` and sign the guestbook. The messages live in the JSON file, so they survive a server restart.

## Where to go next

- **Use a database.** Two requests writing the file at the same time can lose a message. Replace `load-messages` and `add-message!` with SQL through [phel-pdo](https://github.com/phel-lang/phel-pdo). Validate input with [Schema](/documentation/libraries/schema/) before `add-message!`.
- **Deploy it.** Compile ahead of time with `phel build` (see [Deployment](/documentation/guides/deployment/)).
