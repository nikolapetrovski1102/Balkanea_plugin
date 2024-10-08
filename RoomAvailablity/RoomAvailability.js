
function getQueryParams(url) {
    const queryString = url.split('?')[1] || '';
    return new URLSearchParams(queryString);
}

function getPriceFromUrl() {
    const currentUrl = window.location.href;
    
    const params = getQueryParams(currentUrl);
    var multiplier = 1;

    if (params.has('currency')) {
        const currency = params.get('currency');

        if (currency == 'MKD') { multiplier = 61.53; }
    }

    if (params.has('price')) {
        const price = params.get('price') * multiplier;

        console.log(document.querySelector('#mobile-price .price'));
        console.log(document.querySelector('.form-booking-price .price'));

        document.querySelector('#mobile-price .price').innerHTML = `€${price}`;
        document.querySelector('.form-booking-price .price').innerHTML = `€${price}`;
    } else {
        console.log("No price parameter found in the URL.");
    }
}

function getSeachToggle() {
    const currentUrl = window.location.href;
    
    const params = getQueryParams(currentUrl);

    if (params.has('search')) {
        const price = params.get('search');

        setTimeout( () => {
            document.querySelector("#nav-book > div > div > div > div > div > div > form > div.submit-group.button-search-wrapper > button").click();
        }, 200)
        
    } else {
        console.log("No search parameter found in the URL.");
    }
}

function displayPrice(price) {
    
    console.log(document.querySelector('.price').innerHTML);
    
    document.querySelector('.price').innerHTML = price;

}

document.addEventListener('DOMContentLoaded', () => {
    
    getPriceFromUrl();
    getSeachToggle();
    
    const searchButton = document.querySelector("#nav-book > div > div > div > div > div > div > form > div.submit-group.button-search-wrapper > button")

    if ( document.querySelector('.room-rates') ){
        document.querySelector('.room-rates').style.display = 'none';
    }

    if (searchButton === null) throw ("Button not found");

    searchButton.addEventListener('submit', (e) => {
        e.preventDefault();

        let check_in = document.querySelector("#st-list-room > div.st-list-rooms.relative > div.fetch > div > form > input[type=hidden]:nth-child(1)");

        console.log(check_in);
    })
});
