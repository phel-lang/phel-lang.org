document.addEventListener('keyup', function (keyboardEvent) {
    if (keyboardEvent.key === 's'
        || keyboardEvent.key === 'S'
        || keyboardEvent.key === '/'
    ) {
        document.getElementById("search").focus();
    }
});

if (document.readyState === "complete" || (document.readyState !== "loading" && !document.documentElement.doScroll)) {
    initSearch();
} else {
    document.addEventListener("DOMContentLoaded", initSearch);
}

function initSearch() {
    const $searchInput = document.getElementById("search");
    const $searchResults = document.querySelector(".search-results");

    elasticlunr.trimmer = function (token) {
        if (token === null || token === undefined) {
            throw new Error('token should not be undefined');
        }

        return token;
    };
    const index = elasticlunr(function () {
        this.addField('fnName');
        this.addField('desc');
        this.setRef('anchor');
        elasticlunr.stopWordFilter.stopWords = {};
        elasticlunr.Pipeline.registerFunction(elasticlunr.trimmer, 'trimmer');
        elasticlunr.tokenizer.seperator = /[\s~~]+/;
    });
    // Load symbols into elasticlunr object
    window.searchIndexApi.forEach(item => index.addDoc(item));

    $searchInput.addEventListener("keyup", debounce(showResults(index), 150));
    // Hide results when user press on the 'x' placed inside the search field
    $searchInput.addEventListener("search", () => $searchResults.style.display = "");
    $searchInput.addEventListener("focusin", function () {
        if ($searchInput.value !== "") {
            showResults(index)();
        }
    });

    window.addEventListener("click", function (e) {
        if ($searchResults.style.display === "block") {
            if (e.target !== $searchInput) {
                $searchResults.style.display = "";
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
        const $searchInput = document.getElementById("search");
        const $searchResults = document.querySelector(".search-results");
        const $searchResultsItems = document.querySelector(".search-results__items");
        const MAX_ITEMS = 10;

        const term = $searchInput.value.trim();
        $searchResults.style.display = term === "" ? "" : "block";
        $searchResultsItems.innerHTML = "";
        if (term === "") {
            $searchResults.style.display = "";
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

            createMenuItem(emptyResult);
            return;
        }

        const numberOfResults = Math.min(results.length, MAX_ITEMS);
        for (let i = 0; i < numberOfResults; i++) {
            createMenuItem(results[i].doc);
        }
    }
}

function createMenuItem(result) {
    const $searchInput = document.getElementById("search");
    const $searchResultsItems = document.querySelector(".search-results__items");

    const item = document.createElement("li");
    item.innerHTML = formatSearchResultItem(result);
    item.addEventListener('click', () => $searchInput.value = "")
    $searchResultsItems.appendChild(item);
}

function formatSearchResultItem(item) {
    return `<a href="/documentation/api/#${item.anchor}">`
        + `<div class="search-results__item">${item.fnName} `
        + `<small class="fn-signature">${item.fnSignature}</small>`
        + `<span class="desc">${item.desc}</span>`
        + `</div></a>`;
}
