const MAX_ITEMS = 15;
const UP_ARROW = "ArrowUp";
const DOWN_ARROW = "ArrowDown";
const ENTER_KEY = "Enter";
const ESCAPE_KEY = "Escape";

const searchTrigger = document.getElementById("search-trigger");
const searchModal = document.getElementById("search-modal");
const searchModalBackdrop = document.getElementById("search-modal-backdrop");
const searchInput = document.getElementById("search");
const searchResults = document.getElementById("search-results");
const searchResultsItems = document.getElementById("search-results__items");

let searchItemSelected = null;
let resultsItemsIndex = -1;

////////////////////////////////////
// Viewport Height Handler for Mobile
////////////////////////////////////

// Set CSS custom property for viewport height (for browsers without dvh support)
function setViewportHeight() {
    // Get the actual viewport height (excludes keyboard on mobile)
    const vh = window.visualViewport ? window.visualViewport.height * 0.01 : window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', `${vh}px`);
}

// Initialize and listen for viewport changes
if (window.visualViewport) {
    setViewportHeight();
    window.visualViewport.addEventListener('resize', setViewportHeight);
} else {
    // Fallback for browsers without visualViewport API
    setViewportHeight();
    window.addEventListener('resize', setViewportHeight);
}

////////////////////////////////////
// Modal Management
////////////////////////////////////

function openSearchModal() {
    searchModal.setAttribute("aria-hidden", "false");
    
    // Detect iOS devices
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    
    if (isIOS) {
        // iOS-specific scroll lock (position: fixed approach)
        const scrollY = window.scrollY;
        document.body.style.position = "fixed";
        document.body.style.top = `-${scrollY}px`;
        document.body.style.width = "100%";
        document.body.style.overflow = "hidden";
        document.body.setAttribute("data-scroll-y", scrollY.toString());
    } else {
        // Simple overflow: hidden for other browsers
        document.body.style.overflow = "hidden";
    }

    // Focus the search input after a brief delay to ensure modal is visible
    // Longer delay for iOS to account for position: fixed layout changes
    const isMobile = window.innerWidth <= 768;
    const delay = isIOS ? 300 : (isMobile ? 200 : 100);
    
    // On mobile, hide results when input is empty (input stays visible)
    if (isMobile && searchResults) {
        searchResults.style.display = searchInput.value.trim() === "" ? "none" : "block";
    }
    
    setTimeout(() => {
        searchInput.focus();
        // For mobile, ensure keyboard shows up
        if (isMobile) {
            searchInput.click();
        }
        
        // iOS sometimes needs an additional nudge
        if (isIOS) {
            setTimeout(() => {
                searchInput.focus();
                searchInput.click();
            }, 50);
        }
    }, delay);
}

function closeSearchModal() {
    searchModal.setAttribute("aria-hidden", "true");
    
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    
    if (isIOS) {
        // iOS-specific: restore scroll position
        const scrollY = document.body.getAttribute("data-scroll-y");
        document.body.style.position = "";
        document.body.style.top = "";
        document.body.style.width = "";
        document.body.style.overflow = "";
        document.body.removeAttribute("data-scroll-y");
        
        if (scrollY) {
            window.scrollTo(0, parseInt(scrollY));
        }
    } else {
        // Simple overflow restore for other browsers
        document.body.style.overflow = "";
    }
    
    searchInput.value = "";
    searchResults.style.display = "none";
    searchItemSelected = null;
    resultsItemsIndex = -1;
}

// Open modal when trigger button is clicked
if (searchTrigger) {
    searchTrigger.addEventListener("click", openSearchModal);
}

// Close modal when backdrop is clicked
if (searchModalBackdrop) {
    searchModalBackdrop.addEventListener("click", closeSearchModal);
}

// Close modal when ESC shortcut indicator is clicked
const searchModalShortcut = document.querySelector(".search-modal__shortcut");
if (searchModalShortcut) {
    searchModalShortcut.addEventListener("click", closeSearchModal);
}

// Focus input when clicking anywhere in the input wrapper (helpful on mobile)
const searchInputWrapper = document.querySelector(".search-modal__input-wrapper");
if (searchInputWrapper) {
    searchInputWrapper.addEventListener("click", (e) => {
        // Don't focus if clicking the ESC button
        if (!e.target.classList.contains("search-modal__shortcut")) {
            searchInput.focus();
        }
    });
}

////////////////////////////////////
// Keyboard shortcuts
////////////////////////////////////

document.addEventListener("keydown", function (keyboardEvent) {
    // Open modal with /, s, or S (only if modal is not already open)
    if (["s", "S", "/"].includes(keyboardEvent.key) && searchModal.getAttribute("aria-hidden") === "true") {
        // Don't trigger if user is typing in an input/textarea
        if (document.activeElement.tagName === "INPUT" ||
            document.activeElement.tagName === "TEXTAREA" ||
            document.activeElement.isContentEditable) {
            return;
        }
        keyboardEvent.preventDefault();
        openSearchModal();
        return;
    }

    // Only handle these keys when modal is open
    if (searchModal.getAttribute("aria-hidden") === "false") {
        const items = searchResultsItems.getElementsByTagName("li");
        const len = items.length - 1;

        switch (keyboardEvent.key) {
            case DOWN_ARROW:
                if (items.length > 0) {
                    keyboardEvent.preventDefault();
                    downArrow(len);
                }
                break;

            case UP_ARROW:
                if (items.length > 0) {
                    keyboardEvent.preventDefault();
                    upArrow(len);
                }
                break;

            case ENTER_KEY: {
                const parent = searchItemSelected || searchResultsItems;
                const target = parent.querySelector("a");

                if (target) {
                    target.click();
                    closeSearchModal();
                }
                break;
            }

            case ESCAPE_KEY:
                keyboardEvent.preventDefault();
                closeSearchModal();
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
// Initialize search
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
        if (obj == null) {
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

    // Search on input
    searchInput.addEventListener("keyup", function (keyboardEvent) {
        if (keyboardEvent.key === DOWN_ARROW || keyboardEvent.key === UP_ARROW || keyboardEvent.key === ENTER_KEY || keyboardEvent.key === ESCAPE_KEY) {
            return;
        }

        searchItemSelected = null;
        resultsItemsIndex = -1;
        debounce(showResults(index), 150)();
    });

    // Hide results when user clears the search field
    searchInput.addEventListener("search", () => {
        if (searchInput.value === "") {
            searchResults.style.display = "none";
        }
    });

    // Show results when input is focused and has value
    searchInput.addEventListener("focus", function () {
        if (searchInput.value.trim() !== "") {
            showResults(index)();
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
        
        searchResults.style.display = term === "" ? "none" : "block";
        searchResultsItems.innerHTML = "";

        if (term === "") {
            searchResults.style.display = "none";
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
                type: "empty"
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
        mouseEvent.currentTarget.classList.add("selected");
        searchItemSelected = mouseEvent.currentTarget;
        resultsItemsIndex = index;
    });

    item.addEventListener("click", () => {
        closeSearchModal();
    });

    searchResultsItems.appendChild(item);
}

function formatSearchResultItem(item) {
    if (item.type === "documentation") {
        return `<a class="search-results__link" href="${item.url}">`
            + `<div class="search-results__item">`
            + `<div class="search-results__header">`
            + `<strong class="title">${item.title}</strong>`
            + `<span class="search-results__badge search-results__badge--docs">Docs</span>`
            + `</div>`
            + `<span class="desc">${item.content}</span>`
            + `</div></a>`;
    } else if (item.type === "empty") {
        return `<a class="search-results__link" href="${item.anchor}">`
            + `<div class="search-results__item">`
            + `<div class="search-results__header">`
            + `<div class="fn-info">`
            + `<span class="fn-name">${item.name}</span> `
            + `<small class="fn-signature">${item.signature}</small>`
            + `</div>`
            + `</div>`
            + `<span class="desc">${item.desc}</span>`
            + `</div></a>`;
    } else {
        return `<a class="search-results__link" href="/documentation/api/#${item.anchor}">`
            + `<div class="search-results__item">`
            + `<div class="search-results__header">`
            + `<div class="fn-info">`
            + `<span class="fn-name">${item.name}</span> `
            + `<small class="fn-signature">${item.signature}</small>`
            + `</div>`
            + `<span class="search-results__badge search-results__badge--api">API</span>`
            + `</div>`
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