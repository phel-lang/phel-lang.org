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

function formatSearchResultItem(item) {
    return `<a href="/documentation/api/#${item.doc.anchor}">`
        + `<div class="search-results__item">${item.doc.fnName} `
        + `<small class="fn-signature">${item.doc.fnSignature}</small>`
        + `<div class="desc">${item.doc.desc}</div>`
        + '</div></a>';
}

function initSearch() {
    const $searchInput = document.getElementById("search");
    const $searchResults = document.querySelector(".search-results");
    const $searchResultsItems = document.querySelector(".search-results__items");
    const MAX_ITEMS = 10;

    let currentTerm = "";

    const index = elasticlunr(function () {
        this.addField('fnName');
        this.addField('anchor');
        this.addField('fnSignature');
        this.addField('desc');
        this.setRef('fnName');
        elasticlunr.stopWordFilter.stopWords = {};
    });
    window.searchIndexApi.forEach(item => index.addDoc(item));

    $searchInput.addEventListener("keyup", debounce(function () {
        const term = $searchInput.value.trim();
        if (term === currentTerm || !index) {
            return;
        }
        $searchResults.style.display = term === "" ? "none" : "block";
        $searchResultsItems.innerHTML = "";
        if (term === "") {
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
            $searchResults.style.display = "none";
            return;
        }

        currentTerm = term;
        for (let i = 0; i < Math.min(results.length, MAX_ITEMS); i++) {
            const item = document.createElement("li");
            item.innerHTML = formatSearchResultItem(results[i]);
            $searchResultsItems.appendChild(item);
        }
    }, 150));

    window.addEventListener('click', function (e) {
        if ($searchResults.style.display === "block") {
            $searchResults.style.display = "none";
            $searchInput.value = "";
        }
    });
}

if (document.readyState === "complete" ||
    (document.readyState !== "loading" && !document.documentElement.doScroll)
) {
    initSearch();
} else {
    document.addEventListener("DOMContentLoaded", initSearch);
}
