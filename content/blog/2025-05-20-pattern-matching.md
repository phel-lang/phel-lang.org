+++
title = "Pattern Matching: Writing Cleaner Code with Less Conditional Logic"
aliases = [ "/blog/pattern-matching" ]
description = "Trade if/elseif chains for case and cond, with PHP-friendly examples that show when each match shines."
+++

Remember those giant `if/elseif/else` ladders we write in PHP? They start off harmless and suddenly fill half a file. Phel ships with friendlier tools so you can keep the logic flat and readable. We will look at the two big helpers - [`case`](/documentation/control-flow/#case) and [`cond`](/documentation/control-flow/#cond) - and how they feel when you are new to Lisp syntax.

## When plain `if` gets messy

Here is the classic situation: a small payload comes from an API and you branch on it. In PHP you might write a long `if/elseif`. In Phel it can look like this:

```phel
(defn classify [event]
  (if (= (:type event) :created)
    "Fresh!"
    (if (= (:type event) :updated)
      (if (:urgent? event)
        "Update (urgent)"
        "Update (normal)")
      (if (= (:type event) :deleted)
        "Gone."
        "Unknown..."))))
```

It works, but the intent hides in the nesting. Pattern matching lets us tell the same story with fewer twists.

## `case`: think `switch`, but without fall-through

When you compare one value against known constants, reach for [`case`](/documentation/control-flow/#case). It feels like PHP's `switch`, minus the accidental fall-through.

```phel
(defn classify [event]
  (case (:type event)
    :created "Fresh!"
    :updated (if (:urgent? event)
               "Update (urgent)"
               "Update (normal)")
    :deleted "Gone."
    (str "Unknown: " (:type event))))
```

Every branch lives on a single level, and the final expression works as a default. No more scrolling to match up closing parentheses.

## `cond`: guard clauses without the ladder

Sometimes you check different conditions in order: heavy parcel, express flag, cancel flag, and so on. [`cond`](/documentation/control-flow/#cond) does exactly that. Give it pairs of condition and result; it returns the first match.

```phel
(defn shipping-label [order]
  (cond
    (:cancelled? order) "Skip shipping, order cancelled."
    (> (:weight order) 30) "Send with freight carrier."
    (:express? order) "Upgrade to express."
    :else "Regular parcel."))
```

Think of it as stacked `elseif` blocks with no braces to juggle. Drop in `:else` for the fallback and you are done.

## Bonus: friendly destructuring

Pattern matching gets even nicer when you unpack data while you branch. Vectors are like PHP arrays with numeric keys, and maps are like associative arrays. You can pull values out right where you need them.

```phel
(defn handle-message [[kind payload]]
  (case kind
    :email (let [{:keys [to subject]} payload]
             (str "Email to " to " about " subject))
    :sms   (let [{:keys [to text]} payload]
             (str "SMS to " to ": " text))
    :push  (let [{:keys [device title]} payload]
             (str "Push notification for " device " -> " title))
    (str "Unknown message: " kind)))
```

`[kind payload]` pulls the first two items out of the vector, and `{:keys [...]}` plucks values from the map by name. No manual `get` calls, no index juggling.

## When to pick what

- **Use `case`** when you would normally reach for `switch` in PHP. One value, many constants.
- **Use `cond`** when each branch has its own test, especially guard clauses.
- **Stick with `if`** for single true/false checks or very hot loops.
- **Layer in destructuring** whenever naming parts of the data makes each branch easier to read.

Once you start matching patterns instead of stacking `if`s, your Phel code reads like a short list of rules. Try it on the next feature that touches conditional logic - you will not miss the ladder.
