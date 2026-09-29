+++
title = "HTML Rendering"
weight = 3
description = "Render HTML from Phel data structures: vectors are elements, maps are attributes, values auto-escape"
aliases = ["/documentation/html-rendering"]
+++

After this page you can build HTML pages from plain Phel data and send them as a response body. Vectors are elements, maps are attributes, and every value is escaped. There is no template language.

## Elements and attributes

`html` from `phel.html` turns a vector into an HTML string. The first item is the tag (keyword or string), an optional map holds the attributes, and the rest is the body: strings, numbers, nested vectors, or lists of them.

```phel
(ns my-app
  (:require phel.html :refer [html]))

(html [:span {:class "foo"} "bar"])       ; => "<span class=\"foo\">bar</span>"
(html ["div"])                            ; => "<div></div>"
(html [:body [:p] [:br]])                 ; => "<body><p></p><br /></body>"
(html [:input {:type "checkbox" :checked true :disabled false}])
; => "<input type=\"checkbox\" checked=\"checked\" />"
```

An attribute set to `true` renders as `name="name"`. An attribute set to `false` or `nil` is left out.

## Escaping

`html` escapes text and attribute values, so user input cannot inject markup:

```phel
(ns my-app
  (:require phel.html :refer [html raw-string]))

(html [:p "<script>alert(1)</script>"])
; => "<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>"

(html [:span (raw-string "<em>trusted</em>")])
; => "<span><em>trusted</em></span>"
```

`raw-string` skips escaping. Use it only for HTML you produced yourself, never for user input.

## Classes and styles

`class` accepts a vector, or a map whose truthy keys become classes. `style` accepts a map:

```phel
(ns my-app
  (:require phel.html :refer [html]))

(html [:div {:class [:a "b"]}])             ; => "<div class=\"a b\"></div>"
(html [:div {:class {:a true :b false}}])   ; => "<div class=\"a\"></div>"
(html [:div {:style {:background "green" :color "red"}} "bar"])
; => "<div style=\"background:green;color:red;\">bar</div>"
```

## Conditions and lists

Use `if` or `when` inside the tree. A `nil` body renders nothing:

```phel
(ns my-app
  (:require phel.html :refer [html]))

(html [:div [:p "a"] (if false [:p "b"] [:p "c"])])  ; => "<div><p>a</p><p>c</p></div>"
(html [:div [:p "a"] (when false [:p "b"])])         ; => "<div><p>a</p></div>"
(html [:ul (for [i :in [3 4 5]] [:li i])])          ; => "<ul><li>3</li><li>4</li><li>5</li></ul>"
```

{% callout(kind="warning") %}
Write each `for` inside the vector literal you pass to `html`. `html` is a macro: it walks that literal at compile time and splices the `for` results into the parent. A `for` inside a helper function is not spliced, and `html` fails with an error like `[:li 1] is not a valid element name`.
{% end %}

<!-- phel-test: skip -->
```phel
(defn item-list [xs] [:ul (for [x :in xs] [:li x])])
(html (item-list [1 2]))
; throws: [:li 1] is not a valid element name.
```

Keep the loop inline and move the markup for one item into a helper:

```phel
(ns my-app
  (:require phel.html :refer [html]))

(defn item [x] [:li x])

(html [:ul (for [x :in [1 2]] (item x))])
; => "<ul><li>1</li><li>2</li></ul>"
```

## Composing reusable fragments

A function that returns a vector is a component. Combine components like any other values, then pass the result to `html` once. The rule above still applies: keep every `for` in the literal you pass to `html`.

```phel
(ns my-app
  (:require phel.html :refer [html doctype]))

(defn nav-link [url label]
  [:a {:href url} label])

(defn layout [title content]
  [:html
   [:head [:title title]]
   [:body
    [:nav (nav-link "/" "Home") (nav-link "/about" "About")]
    content]])

(html (layout "Home" [:p "Welcome"]))
; => "<html><head><title>Home</title></head><body><nav><a href=\"/\">Home</a><a href=\"/about\">About</a></nav><p>Welcome</p></body></html>"

(html (doctype :html5) [:div])
; => "<!DOCTYPE html>\n<div></div>"
```

`doctype` accepts `:html5`, `:xhtml-transitional`, `:xhtml-strict`, and `:html4`.

## Send it as a response

Pass the string to `html-response`, which also sets the `Content-Type` header:

<!-- phel-test: skip -->
```phel
(defn home [request]
  (http/html-response 200 (html (doctype :html5) (layout "Home" [:p "Welcome"]))))
```

See [Request and Response](/documentation/web/http-request-and-response/) for the rest of the cycle, and the [html API reference](/documentation/reference/api/html/) for every helper.
