+++
title = "HTML Rendering"
weight = 3
description = "Render HTML from Phel data structures: vectors are elements, maps are attributes, values auto-escape"
aliases = ["/documentation/html-rendering"]
+++

`phel.html` builds HTML from plain Phel data. A vector is an element, a map holds its attributes, and text is escaped for you.

## Elements and attributes

`html` from `phel.html` turns a vector into an HTML string, with no template language. The first item is the tag (keyword or string), an optional map holds the attributes, and the rest is the body: strings, numbers, nested vectors, or lists of them.

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

(html [:div {:class {:a true :b false}}])   ; => "<div class=\"a\"></div>"
(html [:div {:style {:background "green" :color "red"}} "bar"])
; => "<div style=\"background:green;color:red;\">bar</div>"
```

## Conditions and lists

Use `if` or `when` inside the tree. A `nil` body renders nothing:

```phel
(ns my-app
  (:require phel.html :refer [html]))

(html [:div [:p "a"] (when false [:p "b"])])         ; => "<div><p>a</p></div>"
(html [:ul (for [i :in [3 4 5]] [:li i])])          ; => "<ul><li>3</li><li>4</li><li>5</li></ul>"
```

{% <callout kind="warning"> %}
Write each `for` inside the vector literal you pass to `html`. `html` is a macro: it walks that literal at compile time and splices the `for` results into the parent. A `for` inside a helper function is not spliced, and `html` fails with an error like `[:li 1] is not a valid element name`.
{% </callout> %}

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

## Pages and responses

A function that returns a vector is a component; combine components like any other values and pass the result to `html` once. `doctype` accepts `:html5`, `:xhtml-transitional`, `:xhtml-strict`, and `:html4`:

```phel
(ns my-app
  (:require phel.html :refer [html doctype]))

(html (doctype :html5) [:div])
; => "<!DOCTYPE html>\n<div></div>"
```

Send the string with [`html-response`](/documentation/web/http-request-and-response/#build-a-response). Every helper is in the [html API reference](/documentation/reference/api/html/).
