const MAX_ITEMS = 10;
const UP_ARROW = "ArrowUp";
const DOWN_ARROW = "ArrowDown";
const ENTER_KEY = "Enter";

const searchInput = document.getElementById("search");
const searchResults = document.getElementById("search-results");
const searchResultsItems = document.getElementById("search-results__items");

let searchItemSelected = null;
let resultsItemsIndex = -1;

////////////////////////////////////
// Interaction with the search input
////////////////////////////////////
document.addEventListener("keyup", function (keyboardEvent) {
    if (["s", "S", "/"].includes(keyboardEvent.key)) {
        searchInput.focus();
    }
});

document.addEventListener("keydown", function (keyboardEvent) {
    const len = searchResultsItems.getElementsByTagName("li").length - 1;

    if (keyboardEvent.key === DOWN_ARROW) {
        downArrow(len);
    } else if (keyboardEvent.key === UP_ARROW) {
        upArrow(len);
    } else if (keyboardEvent.key === ENTER_KEY) {
        searchItemSelected.getElementsByTagName("a")[0].click();
    }
});

function downArrow(len) {
    resultsItemsIndex++;

    if (!searchItemSelected) {
        resultsItemsIndex = 0;
        searchItemSelected = searchResultsItems.getElementsByTagName("li")[0];
    } else {
        removeClass(searchItemSelected, "selected");
        const next = searchResultsItems.getElementsByTagName("li")[resultsItemsIndex];

        if (typeof next !== undefined && resultsItemsIndex <= len) {
            searchItemSelected = next;
        } else {
            resultsItemsIndex = 0;
            searchItemSelected = searchResultsItems.getElementsByTagName("li")[0];
        }
    }

    searchItemSelected.focus()
    addClass(searchItemSelected, "selected");
}

function upArrow(len) {
    if (!searchItemSelected) {
        resultsItemsIndex = -1;
        searchItemSelected = searchResultsItems.getElementsByTagName("li")[len];
    } else {
        removeClass(searchItemSelected, "selected");
        resultsItemsIndex--;
        const next = searchResultsItems.getElementsByTagName("li")[resultsItemsIndex];

        if (typeof next !== undefined && resultsItemsIndex >= 0) {
            searchItemSelected = next;
        } else {
            resultsItemsIndex = len;
            searchItemSelected = searchResultsItems.getElementsByTagName("li")[len];
        }
    }
    searchItemSelected.focus()
    addClass(searchItemSelected, "selected");
}

function removeClass(el, className) {
    if (el.classList) {
        el.classList.remove(className);
    } else {
        el.className = el.className.replace(new RegExp("(^|\\b)" + className.split(" ").join("|") + "(\\b|$)", "gi"), " ");
    }
}

function addClass(el, className) {
    if (el.classList) {
        el.classList.add(className);
    } else {
        el.className += " " + className;
    }
}

///////////////////////////////
// Autoload of the search input
///////////////////////////////
if (document.readyState === "complete" || (document.readyState !== "loading" && !document.documentElement.doScroll)) {
    initSearch();
} else {
    document.addEventListener("DOMContentLoaded", initSearch);
}

function initSearch() {
    elasticlunr.trimmer = function (token) {
        if (token === null || token === undefined) {
            throw new Error("token should not be undefined");
        }

        return token;
    };
    const index = elasticlunr(function () {
        this.addField("fnName");
        this.addField("desc");
        this.setRef("anchor");
        elasticlunr.stopWordFilter.stopWords = {};
        elasticlunr.Pipeline.registerFunction(elasticlunr.trimmer, "trimmer");
        elasticlunr.tokenizer.seperator = /[\s~~]+/;
    });
    // Load symbols into elasticlunr object
    window.searchIndexApi.forEach(item => index.addDoc(item));

    searchInput.addEventListener("keyup", function (keyboardEvent) {
        if (keyboardEvent.key === DOWN_ARROW || keyboardEvent.key === UP_ARROW || keyboardEvent.key === ENTER_KEY) {
            return;
        }

        searchItemSelected = null;
        resultsItemsIndex = -1;
        debounce(showResults(index), 150)();
    });

    // Hide results when user press on the "x" placed inside the search field
    searchInput.addEventListener("search", () => searchResults.style.display = "");
    searchInput.addEventListener("focusin", function () {
        if (searchInput.value !== "") {
            showResults(index)();
        }
    });

    searchInput.addEventListener("focusout", function () {
        resultsItemsIndex = -1;
    });

    window.addEventListener("click", function (mouseEvent) {
        if (searchResults.style.display === "block") {
            if (mouseEvent.target !== searchInput) {
                searchResults.style.display = "";
            }
        }
    });
}

function debounce(func, wait) {
    let timeout;

    return function () {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);

        timeout = setTimeout(function () {
            timeout = null;
            func.apply(context, args);
        }, wait);
    };
}

function showResults(index) {
    return function () {
        const term = searchInput.value.trim();
        searchResults.style.display = term === "" ? "" : "block";
        searchResultsItems.innerHTML = "";
        if (term === "") {
            searchResults.style.display = "";
            return;
        }

        const options = {
            bool: "AND",
            fields: {
                fnName: {boost: 3},
                desc: {boost: 1},
            },
            expand: true
        };
        const results = index.search(term, options);
        if (results.length === 0) {
            let emptyResult = {
                fnName: "Symbol not found",
                fnSignature: "",
                desc: "Cannot provide any Phel symbol. Try something else",
                anchor: "#",
            };

            createMenuItem(emptyResult, null);
            return;
        }

        const numberOfResults = Math.min(results.length, MAX_ITEMS);
        for (let i = 0; i < numberOfResults; i++) {
            createMenuItem(results[i].doc, i);
        }
    }
}

function createMenuItem(result, index) {
    const item = document.createElement("li");
    item.innerHTML = formatSearchResultItem(result);
    item.addEventListener("mouseenter", (mouseEvent) => {
        removeSelectedClassFromSearchResult();
        mouseEvent.target.classList.add("selected");
        searchItemSelected = mouseEvent.target;
        resultsItemsIndex = index;
    })
    item.addEventListener("click", () => searchInput.value = "")
    searchResultsItems.appendChild(item);
}

function formatSearchResultItem(item) {
    return `<a href="/documentation/api/#${item.anchor}">`
        + `<div class="search-results__item">${item.fnName} `
        + `<small class="fn-signature">${item.fnSignature}</small>`
        + `<span class="desc">${item.desc}</span>`
        + `</div></a>`;
}

function removeSelectedClassFromSearchResult() {
    const searchResultsItemChildren = searchResultsItems.children;
    for (let i = 0; i < searchResultsItemChildren.length; i++) {
        removeClass(searchResultsItemChildren[i], "selected")
    }
}
