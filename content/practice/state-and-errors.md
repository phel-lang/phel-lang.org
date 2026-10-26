+++
title = "State and Errors"
weight = 8
description = "Hold changing state in an atom, keep the logic pure, and handle failures by throwing with ex-info or by returning errors as data."

[extra]
stage = "Real world"
goals = [
  "Create, read, and update an atom with `atom`, `@`, `swap!`, and `reset!`",
  "Write pure functions first, then wire them to an atom with `swap!`",
  "Throw and catch errors with `throw`, `try`, `catch`, and `finally`",
  "Attach data to an error with `ex-info` and read it with `ex-data`",
  "Choose between throwing and returning an error map",
]
read_first = [
  ["Atoms", "/documentation/language/global-and-local-bindings/#atoms"],
  ["Error Handling", "/documentation/language/error-handling/"],
]
recap = [
  "You can keep a changing value in an atom and update it with a pure function",
  "You can test your business rules without any atom in sight",
  "You can throw an error that carries data, and read that data where you catch it",
  "You can return errors as plain maps when the caller should decide what to do",
]
+++

Until now every value you wrote stayed the same forever. Real programs also need things that change: a bank balance, a shopping cart, a counter. Phel keeps that change in one small box, the atom, and keeps the rules that decide the change in plain pure functions. Things also go wrong in real programs, so the second half of this module covers errors: when to throw one, and when to return it as data.

## Atoms

An atom holds one value that can change. Read it with `@` (or `deref`). Change it with `swap!`, which applies a function to the current value, or `reset!`, which replaces it. Both return the new value.

```phel
(def visits (atom 0))

(swap! visits inc)  ; => 1
(reset! visits 100) ; => 100
@visits             ; => 100
```

{% <question difficulty="easy" kind="predict"> %}
What is the last value?
```phel
(def counter (atom 0))
(swap! counter inc)
(swap! counter + 5)
@counter
```
{% </question> %}
{% <solution> %}
```phel
(def counter (atom 0))
(swap! counter inc)
(swap! counter + 5)
@counter ; => 6
```
`swap!` calls the function with the current value first, then any extra arguments. So `(swap! counter + 5)` computes `(+ 1 5)`.
{% </solution> %}

{% <question difficulty="easy" kind="predict"> %}
What does this return?
```phel
(def score (atom 10))

(let [before @score]
  (reset! score 0)
  [before @score])
```
{% </question> %}
{% <solution> %}
```phel
(def score (atom 10))

(let [before @score]
  (reset! score 0)
  [before @score]) ; => [10 0]
```
`@score` gives you the value inside the atom at that moment. The value `10` is immutable: `reset!` points the atom at a new value, and `before` still holds the old one.
{% </solution> %}

{% <question difficulty="easy" kind="fill"> %}
The cart atom holds a map. Fill the blanks to add one to `:count` and add `"pen"` to `:items`.
<!-- phel-test: skip -->
```phel
(def cart (atom {:items [] :count 0}))

(swap! cart ___ :count ___)
(swap! cart ___ :items ___ "pen")

@cart ; => {:items ["pen"], :count 1}
```
{% </question> %}
{% <solution> %}
```phel
(def cart (atom {:items [] :count 0}))

(swap! cart update :count inc)
(swap! cart update :items conj "pen")

@cart ; => {:items ["pen"], :count 1}
```
`(swap! cart update :count inc)` runs `(update current-cart :count inc)`. Everything you know about updating maps works inside `swap!`.
{% </solution> %}

## Pure logic first

Keep the rules in pure functions: they take a value and return a new value. The atom is only the place where the result is stored. A pure function is easy to test (call it, check the result) and easy to reuse.

```phel
(defn add-visit [stats page]
  (update stats page inc))

(add-visit {"/home" 1} "/home") ; => {"/home" 2}
```

{% <question difficulty="easy" kind="write"> %}
Write a pure function `deposit`. It takes an account map and an amount, and returns the account with a higher `:balance`. No atom yet.
<!-- phel-test: skip -->
```phel
(deposit {:owner "Ada" :balance 100} 50) ; => {:owner "Ada", :balance 150}
```
{% </question> %}
{% <solution> %}
```phel
(defn deposit [account amount]
  (update account :balance + amount))

(deposit {:owner "Ada" :balance 100} 50) ; => {:owner "Ada", :balance 150}
```
`update` passes extra arguments to the function, so this runs `(+ 100 50)`. The same `(fn [x] (+ x amount))` works too, but is longer.
{% </solution> %}

{% <question difficulty="medium" kind="write"> %}
Now wire `deposit` to an atom. Write `deposit!`, which takes the atom and an amount, updates the atom, and returns the new account. Do not change `deposit`.
<!-- phel-test: skip -->
```phel
(def account (atom {:owner "Ada" :balance 100}))

(deposit! account 50) ; => {:owner "Ada", :balance 150}
@account              ; => {:owner "Ada", :balance 150}
```
{% </question> %}
{% <hint> %}
`swap!` takes the atom, a function, and extra arguments for that function. `deposit` already has the right shape: account first, amount second.
{% </hint> %}
{% <solution> %}
```phel
(defn deposit [account amount]
  (update account :balance + amount))

(defn deposit! [account-atom amount]
  (swap! account-atom deposit amount))

(def account (atom {:owner "Ada" :balance 100}))

(deposit! account 50) ; => {:owner "Ada", :balance 150}
@account              ; => {:owner "Ada", :balance 150}
```
The `!` at the end of the name is a convention: it warns the reader that the function changes state. `deposit` holds the rule; `deposit!` is one line of wiring.
{% </solution> %}

{% <question difficulty="medium" kind="refactor"> %}
This `withdraw!` works, but the rule and the state are tangled. It reads the atom twice and you cannot test the subtraction without an atom. Split it into a pure `withdraw` and one `swap!` call.
```phel
(def account (atom {:owner "Ada" :balance 100}))

(defn withdraw! [amount]
  (reset! account (assoc @account :balance (- (:balance @account) amount))))

(withdraw! 30) ; => {:owner "Ada", :balance 70}
```
{% </question> %}
{% <hint> %}
Write `withdraw` like `deposit`: account in, account out. Then let `swap!` pass the current value to it.
{% </hint> %}
{% <solution> %}
```phel
(defn withdraw [account amount]
  (update account :balance - amount))

(def account (atom {:owner "Ada" :balance 100}))

(swap! account withdraw 30) ; => {:owner "Ada", :balance 70}
```
`withdraw` now works on any account map, including one in a test. `swap!` reads and writes in one step. The old version read the atom twice, and another update between those reads would be lost.
{% </solution> %}

## Throwing errors

`throw` stops the current code and jumps to the nearest matching `catch`. `ex-info` builds an error with a message and a map of data. In the `catch`, `ex-message` and `ex-data` read them back. `finally` runs last, whether an error happened or not.

```phel
(try
  (throw (ex-info "Out of stock" {:sku "pen"}))
  (catch \Exception e
    (str (ex-message e) ": " (:sku (ex-data e)))))
; => "Out of stock: pen"
```

In PHP you would write `throw new \Exception(...)` and `catch (\Exception $e)`. `ex-info` is still a PHP exception, with a data map attached.

{% <question difficulty="easy" kind="predict"> %}
What does this return?
```phel
(try
  (throw (ex-info "Too large" {:max 100 :got 250}))
  (catch \Exception e
    (- (:got (ex-data e)) (:max (ex-data e)))))
```
{% </question> %}
{% <solution> %}
```phel
(try
  (throw (ex-info "Too large" {:max 100 :got 250}))
  (catch \Exception e
    (- (:got (ex-data e)) (:max (ex-data e)))))
; => 150
```
`ex-data` returns the map you passed to `ex-info`. The handler can compute with it instead of parsing the message string.
{% </solution> %}

{% <question difficulty="medium" kind="predict"> %}
What does this print, and what does it return?
```phel
(try
  (println "start")
  (throw (ex-info "boom" {}))
  (println "after")
  (catch \Exception e
    (println "caught")
    :failed)
  (finally
    (println "done")))
```
{% </question> %}
{% <hint> %}
After a `throw`, no more lines of the body run. The value of `finally` is thrown away.
{% </hint> %}
{% <solution> %}
```phel
(try
  (println "start")
  (throw (ex-info "boom" {}))
  (println "after")
  (catch \Exception e
    (println "caught")
    :failed)
  (finally
    (println "done")))
; prints "start", "caught", "done"
; => :failed
```
`"after"` never prints. The `catch` value `:failed` is the result of the whole `try`. `finally` runs for cleanup, like closing a file, but it does not change the result.
{% </solution> %}

{% <question difficulty="medium" kind="write"> %}
Change `withdraw` so it refuses to go below zero. When the amount is larger than the balance, throw an `ex-info` with the message `"Insufficient funds"` and the data `{:balance ... :amount ...}`.
<!-- phel-test: skip -->
```phel
(withdraw {:owner "Ada" :balance 100} 30) ; => {:owner "Ada", :balance 70}

(try
  (withdraw {:owner "Ada" :balance 100} 500)
  (catch \Exception e
    [(ex-message e) (ex-data e)]))
; => ["Insufficient funds" {:balance 100, :amount 500}]
```
{% </question> %}
{% <hint> %}
Check first with `when`, throw inside it, then do the normal `update` as the last form.
{% </hint> %}
{% <solution> %}
```phel
(defn withdraw [account amount]
  (when (> amount (:balance account))
    (throw (ex-info "Insufficient funds"
                    {:balance (:balance account) :amount amount})))
  (update account :balance - amount))

(withdraw {:owner "Ada" :balance 100} 30) ; => {:owner "Ada", :balance 70}

(try
  (withdraw {:owner "Ada" :balance 100} 500)
  (catch \Exception e
    [(ex-message e) (ex-data e)]))
; => ["Insufficient funds" {:balance 100, :amount 500}]
```
The function is still pure: same input, same result or same error. The data map tells the caller exactly what went wrong, so it can show a useful message.
{% </solution> %}

{% <question difficulty="medium" kind="fix"> %}
After a failed withdrawal, the account is left at `-400`. The error is thrown, but too late. Fix `withdraw!` so a failed withdrawal leaves the atom unchanged.
<!-- phel-test: skip -->
```phel
(def account (atom {:owner "Ada" :balance 100}))

(defn withdraw! [amount]
  (swap! account update :balance - amount)
  (when (neg? (:balance @account))
    (throw (ex-info "Insufficient funds" {:amount amount}))))

(try (withdraw! 500) (catch \Exception e (ex-message e)))
@account ; => {:owner "Ada", :balance -400} (wrong)
```
{% </question> %}
{% <hint> %}
Check before you change, not after. If the function you give to `swap!` throws, `swap!` does not store anything.
{% </hint> %}
{% <solution> %}
```phel
(defn withdraw [account amount]
  (when (> amount (:balance account))
    (throw (ex-info "Insufficient funds"
                    {:balance (:balance account) :amount amount})))
  (update account :balance - amount))

(def account (atom {:owner "Ada" :balance 100}))

(defn withdraw! [amount]
  (swap! account withdraw amount))

(try (withdraw! 500) (catch \Exception e (ex-message e))) ; => "Insufficient funds"
@account ; => {:owner "Ada", :balance 100}
```
Validation now lives in the pure `withdraw`, which runs inside `swap!`. When it throws, the new value is never stored, so the atom never holds an invalid balance.
{% </solution> %}

## Errors as data

Throwing is not the only option. A function can return a map that says what happened, and the caller checks it like any other data. Use a key like `:ok` for success and `:error` for failure.

```phel
(defn check-age [age]
  (if (>= age 18)
    {:ok age}
    {:error "too young"}))

(check-age 20) ; => {:ok 20}
(check-age 15) ; => {:error "too young"}
```

Which one to use?

- **Throw** when the caller cannot sensibly go on: a broken rule deep inside your logic, a bug, a failed database call. The error travels up until someone who can handle it catches it.
- **Return data** when failure is a normal, expected answer: user input that does not validate, a form with several wrong fields. The caller must handle it anyway, and data is easier to inspect, collect, and test.

{% <question difficulty="medium" kind="write"> %}
Write `validate-transfer`. It returns a vector of every problem it finds, or `[]` when the transfer is valid. Throwing could only report the first problem; a form should show all of them.

- `"amount is required"` when there is no `:amount`
- `"amount must be positive"` when the amount is zero or less
- `"cannot transfer to yourself"` when `:from` and `:to` are equal

<!-- phel-test: skip -->
```phel
(validate-transfer {:from "Ada" :to "Linus" :amount 50}) ; => []
(validate-transfer {:from "Ada" :to "Ada" :amount -5})
; => ["amount must be positive" "cannot transfer to yourself"]
(validate-transfer {:from "Ada" :to "Linus"}) ; => ["amount is required"]
```
{% </question> %}
{% <hint> %}
Build a vector of `[failed? message]` pairs. Keep the pairs whose first element is truthy, then take the messages. Careful: `(<= nil 0)` is not a check you want to run.
{% </hint> %}
{% <solution> %}
```phel
(defn validate-transfer [{:keys [from to amount]}]
  (let [checks [[(nil? amount) "amount is required"]
                [(and amount (<= amount 0)) "amount must be positive"]
                [(= from to) "cannot transfer to yourself"]]]
    (->> checks
         (filter first)
         (map second)
         (into []))))

(validate-transfer {:from "Ada" :to "Linus" :amount 50}) ; => []
(validate-transfer {:from "Ada" :to "Ada" :amount -5})
; => ["amount must be positive" "cannot transfer to yourself"]
(validate-transfer {:from "Ada" :to "Linus"}) ; => ["amount is required"]
```
Each rule is one line of data, so adding a rule is adding a pair. `(and amount ...)` skips the comparison when the amount is missing.
{% </solution> %}

{% <question difficulty="hard" kind="write"> %}
Write `deposit!` again, this time for untrusted input. It returns `{:ok new-account}` and updates the atom when the amount is a positive number. Otherwise it returns `{:error message}` and leaves the atom alone. Reuse the pure `deposit`, and put the input check in its own pure function `amount-error`, which returns a message or `nil`.
<!-- phel-test: skip -->
```phel
(def account (atom {:owner "Ada" :balance 100}))

(deposit! account 50)     ; => {:ok {:owner "Ada", :balance 150}}
(deposit! account -5)     ; => {:error "amount must be positive"}
(deposit! account "ten")  ; => {:error "amount must be a number"}
@account                  ; => {:owner "Ada", :balance 150}
```
{% </question> %}
{% <hint> %}
`amount-error` is a `cond` with no `:else`, so it returns `nil` when every check passes. In `deposit!`, `if-let` binds the message when there is one.
{% </hint> %}
{% <solution> %}
```phel
(defn deposit [account amount]
  (update account :balance + amount))

(defn amount-error [amount]
  (cond
    (not (number? amount)) "amount must be a number"
    (<= amount 0) "amount must be positive"))

(defn deposit! [account-atom amount]
  (if-let [error (amount-error amount)]
    {:error error}
    {:ok (swap! account-atom deposit amount)}))

(def account (atom {:owner "Ada" :balance 100}))

(deposit! account 50)    ; => {:ok {:owner "Ada", :balance 150}}
(deposit! account -5)    ; => {:error "amount must be positive"}
(deposit! account "ten") ; => {:error "amount must be a number"}
@account                 ; => {:owner "Ada", :balance 150}
```
Two pure functions hold the rules, and `deposit!` is the only place that touches state. Bad input is an expected answer here, so it comes back as data instead of an exception.
{% </solution> %}

{% <question difficulty="hard" kind="write"> %}
Your `withdraw` throws, but a web handler wants data: a status code and a body. Write `handle-withdraw`, which calls `withdraw` and turns the result, or the error, into a response map. Do not change `withdraw`.
<!-- phel-test: skip -->
```phel
(handle-withdraw {:owner "Ada" :balance 100} 30)
; => {:status 200, :account {:owner "Ada", :balance 70}}

(handle-withdraw {:owner "Ada" :balance 100} 500)
; => {:status 422, :error "Insufficient funds", :details {:balance 100, :amount 500}}
```
{% </question> %}
{% <hint> %}
Wrap the call in `try`. The success map goes in the body, the error map goes in the `catch`.
{% </hint> %}
{% <solution> %}
```phel
(defn withdraw [account amount]
  (when (> amount (:balance account))
    (throw (ex-info "Insufficient funds"
                    {:balance (:balance account) :amount amount})))
  (update account :balance - amount))

(defn handle-withdraw [account amount]
  (try
    {:status 200 :account (withdraw account amount)}
    (catch \Exception e
      {:status 422 :error (ex-message e) :details (ex-data e)})))

(handle-withdraw {:owner "Ada" :balance 100} 30)
; => {:status 200, :account {:owner "Ada", :balance 70}}

(handle-withdraw {:owner "Ada" :balance 100} 500)
; => {:status 422, :error "Insufficient funds", :details {:balance 100, :amount 500}}
```
This is the common split: throw deep inside the logic, and turn errors into data at the edge of the program, where you know who the answer is for. The `ex-data` map passes straight through to the response.
{% </solution> %}
