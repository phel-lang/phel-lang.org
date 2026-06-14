+++
title = "HTML Rendering"
weight = 3
description = "Render HTML from Phel data structures: vectors are elements, maps are attributes, values auto-escape"
aliases = ["/documentation/html-rendering"]
+++

Build HTML from plain Phel data: vectors are elements, maps are attributes, and values auto-escape for XSS protection. No template language to learn, just the data structures you already use.

## Syntax

`html` from `phel.html` generates HTML:

```phel
(ns my-namespace
  (:require phel.html :refer [html]))

(html [:span {:class "foo"} "bar"])
;; Evaluates to <span class="foo">bar</span>
```

Forms:

<!-- phel-test: skip -->
```phel
[tag body+]
[tag attributes body+]
```

First item: tag name (keyword or string). Second item: optional attribute map. Rest: body (strings, nested vectors, lists).

```phel
(ns my-app
  (:require phel.html :refer [html]))

(html [:div]) ; Evaluates to "<div></div>"
(html ["div"]) ; Evaluates to "<div></div>"
(html [:text "Lorem Ipsum"]) ; Evaluates to "<text>Lorem Ipsum</text>"
(html [:body [:p] [:br]]) ; Evaluates to "<body><p></p><br /></body>"
(html [:div {:id "foo"}]) ; Evaluates to "<div id=\"foo\"></div>"
```

## Classes and styles

Phel enhances `class` and `style` attributes.

Use a map for styles instead of a string. Both forms equivalent:

```phel
(ns my-app
  (:require phel.html :refer [html]))

(html [:div {:style "background:green;color:red;"} "bar"])
(html [:div {:style {:background "green" :color "red"}} "bar"])
;; Both evaluate to
;; "<div style=\"background:green;color:red;\">bar</div>"
```

Class lists: vector or map. Map keys are class names; only truthy keys appear in the final list.

```phel
(ns my-app
  (:require phel.html :refer [html]))

(html [:div {:class [:a]}]) ; <div class=\"a\"></div>
(html [:div {:class [:a "b"]}]) ; <div class=\"a b\"></div>
(html [:div {:class [:a :b]}]) ; <div class=\"a b\"></div>
(html [:div {:class {:a true :b false}}]) ; <div class=\"a\"></div>
```

## Conditional rendering

Use `if`:

```phel
(ns my-app
  (:require phel.html :refer [html]))

(html [:div [:p "a"] (if true [:p "b"] [:p "c"])])
;; Evaluates to "<div><p>a</p><p>b</p></div>"
(html [:div [:p "a"] (if false [:p "b"] [:p "c"])])
;; Evaluates to "<div><p>a</p><p>c</p></div>"
```

## Rendering sequential data

`for` over vectors, lists, sets:

```phel
(ns my-app
  (:require phel.html :refer [html]))

(html [:ul (for [i :range [0 3]] [:li i])])
;; Evaluates to "<ul><li>0</li><li>1</li><li>2</li></ul>"

(html [:ul (for [i :in [3 4 5]] [:li i])])
;; Evaluates to "<ul><li>3</li><li>4</li><li>5</li></ul>"
```

## Raw HTML

Values auto-escape for XSS protection. For unescaped output, use `raw-string`:

```phel
(ns my-app
  (:require phel.html :refer [html raw-string]))

(html [:span (raw-string "<a></a>")])
;; Evaluates to "<span><a></a></span>"
```

## Doctypes

Use `doctype` for the document doctype:

```phel
(ns my-app
  (:require phel.html :refer [html doctype]))

(html (doctype :html5) [:div])
;; Evaluates to "<!DOCTYPE html>\n<div></div>"
```

Supported values: `:html5`, `:xhtml-transitional`, `:xhtml-strict`, `:html4`.

## Composing reusable fragments

Because elements are just vectors, a function that returns a vector is a reusable component. Compose them like any other Phel value, then pass the result to `html` once at the end.

```phel
(ns my-app
  (:require phel.html :refer [html]))

(defn nav-link [url label]
  [:a {:href url} label])

(defn layout [title content]
  [:html
   [:head [:title title]]
   [:body
    [:nav (nav-link "/" "Home") (nav-link "/about" "About")]
    content]])

(html (layout "Home" [:p "Welcome"]))
;; Evaluates to
;; "<html><head><title>Home</title></head><body>
;;  <nav><a href=\"/\">Home</a><a href=\"/about\">About</a></nav><p>Welcome</p></body></html>"
```

Return a fragment from a route handler to produce the response body. See [Request and Response](/documentation/web/http-request-and-response/) and [Routing](/documentation/web/routing/) for wiring fragments into responses.

## Next steps

- [Request and Response](/documentation/web/http-request-and-response/) - send rendered HTML as a response body
- [Routing](/documentation/web/routing/) - map URLs to handlers that return HTML
- [html API reference](/documentation/reference/api/html/) - full list of helpers
