const MAX_ITEMS = 10;
const UP_ARROW = "ArrowUp";
const DOWN_ARROW = "ArrowDown";
const ENTER_KEY = "Enter";
const ESCAPE_KEY = "Escape";

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
    const items = searchResultsItems.getElementsByTagName("li");
    const len = items.length - 1;

    switch (keyboardEvent.key) {
        case DOWN_ARROW:
            keyboardEvent.preventDefault();
            downArrow(len);
            break;

        case UP_ARROW:
            keyboardEvent.preventDefault();
            upArrow(len); 
            break;

        case ENTER_KEY: {
            const parent = searchItemSelected || searchResultsItems;
            const target = parent.querySelector("a");

            if (target) target.click();
            break;
        }

        case ESCAPE_KEY: {
            searchInput.value = "";
            searchResults.style.display = "none";
            searchInput.blur();
            break;
        }
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
    searchItemSelected.scrollIntoView({ block: "nearest" });
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
    searchItemSelected.focus();
    searchItemSelected.scrollIntoView({ block: "nearest" });
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
        this.addField("name");
        this.addField("desc");
        this.addField("title");
        this.addField("content");
        this.setRef("id");
        elasticlunr.stopWordFilter.stopWords = {};
        elasticlunr.Pipeline.registerFunction(elasticlunr.trimmer, "trimmer");
        elasticlunr.tokenizer.seperator = /[\s~~]+/;
    });
    
    // Custom tokenizer to handle symbols with '/'
    const originalTokenizer = elasticlunr.tokenizer;
    elasticlunr.tokenizer = function (obj, metadata) {
        if (obj == null || obj == undefined) {
            return [];
        }
        
        if (Array.isArray(obj)) {
            return obj.reduce(function (tokens, token) {
                return tokens.concat(elasticlunr.tokenizer(token, metadata));
            }, []);
        }
        
        const str = obj.toString().toLowerCase();
        const tokens = originalTokenizer(str, metadata);
        
        // Add additional tokens for strings containing '/'
        if (str.includes('/')) {
            const parts = str.split('/');
            if (parts.length > 1) {
                const lastPart = parts[parts.length - 1];
                if (lastPart) {
                    tokens.push(lastPart);
                }
            }
        }
        
        return tokens;
    };
    
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
            bool: "OR",
            fields: {
                name: {boost: 3},
                title: {boost: 2},
                desc: {boost: 1},
                content: {boost: 1}
            },
            expand: true
        };
        const results = index.search(term, options);
        if (results.length === 0) {
            let emptyResult = {
                name: "Symbol not found",
                signature: "",
                desc: "Cannot provide any Phel symbol. Try something else",
                anchor: "#",
                type: "api"
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
    if (item.type === "documentation") {
        return `<a href="${item.url}">`
            + `<div class="search-results__item">`
            + `<span class="result-type">Documentation: </span>`
            + `<strong>${item.title}</strong>`
            + `<span class="desc">${item.content}</span>`
            + `</div></a>`;
    } else {
        return `<a href="/documentation/api/#${item.anchor}">`
            + `<div class="search-results__item">`
            + `<span class="result-type">API: </span>`
            + `${item.name} `
            + `<small class="fn-signature">${item.signature}</small>`
            + `<span class="desc">${item.desc}</span>`
            + `</div></a>`;
    }
}

function removeSelectedClassFromSearchResult() {
    const searchResultsItemChildren = searchResultsItems.children;
    for (let i = 0; i < searchResultsItemChildren.length; i++) {
        removeClass(searchResultsItemChildren[i], "selected")
    }
}
