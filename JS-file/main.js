
function getQueryParams(url) {
    const queryString = url.split('?')[1] || '';
    return new URLSearchParams(queryString);
}

function formatDate(date) {

    if (date === null || date === '' || date === undefined) return -1;
    
    let date_years = date.split('/')[2];
    let date_months = date.split('/')[1];
    let date_days = date.split('/')[0];

    let _date = date_years + '-' + date_months + '-' + date_days;

    return _date;
}

function setCookie(name, value, minutes) {
    console.log(`Setting ${name} with value ${value}`);
    let expires = "";
    if (minutes) {
        const date = new Date();
        date.setTime(date.getTime() + (minutes * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/; SameSite=Lax";
}

function getPriceFromUrl() {
    const currentUrl = window.location.href;
    
    const params = getQueryParams(currentUrl);

    if (params.has('price')) {
        const price = params.get('price');

        // document.querySelector('#mobile-price .price').innerHTML = `${price}`;
        // document.querySelector('.form-booking-price .price').innerHTML = `${price}`;
    }
}

function getSeachToggle() {

    const search_button = document.querySelector("#nav-book > div > div > div > div > div > div > form > div.submit-group.button-search-wrapper > button");
    
    if (search_button){
        setTimeout( () => {
            document.querySelector("#nav-book > div > div > div > div > div > div > form > div.submit-group.button-search-wrapper > button").click();
        }, 200)
    }
}

function displayPrice(price) {
    
    document.querySelector('.price').innerHTML = price;

}

async function getNonce() {
    try {
        let nonceResponse = await fetch("https://staging.balkanea.com/wp-plugin/APIs/generate-nonce.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ action: "filter_hotel" })
        });

        if (!nonceResponse.ok) {
            throw new Error(`HTTP error! Status: ${nonceResponse.status}`);
        }

        let nonceData = await nonceResponse.json();

        if (!nonceData || !nonceData.nonce) {
            throw new Error("Failed to retrieve nonce.");
        }

        let nonce = nonceData.nonce;
        return nonce;

    } catch (error) {
        console.error("Error fetching nonce:", error.message);
        return null;
    }
}

document.addEventListener('DOMContentLoaded', () => {

    getPriceFromUrl();
    getSeachToggle();
    
    const searchButtonHome = document.querySelector("#nav-st_hotel > div > div > div > form > div.button-search-wrapper > button");
    const searchButton = document.querySelector("#nav-book > div > div > div > div > div > div > form > div.submit-group.button-search-wrapper > button")
    const dropdown = document.querySelector("#nav-st_hotel > div > div > div > form > div.destination-search.border-right > div.dropdown-menu > ul")
    const date_now = new Date();

    if ( document.querySelector('.room-rates') ){
        document.querySelector('.room-rates').style.display = 'none';
        document.querySelector('.relate-rooms').style.display = 'none';
    }

    if (searchButtonHome === null) return;
    else {
        
        var nonce;
        
        getNonce().then(nonce_response => {
            if (nonce_response) {
                console.log(nonce_response);
                nonce = nonce_response;
            }
        });
        
        searchButtonHome.disabled = true;
        searchButtonHome.addEventListener('click', (event) => {
            
            console.log(nonce);
            
            searchButtonHome.querySelector('span').classList.remove("stt-icon", "stt-icon-search-normal");
            searchButtonHome.querySelector('span').classList.add('fa', 'fa-spinner', 'fa-spin');

            event.preventDefault();
        
            const checkInDate = document.querySelector('.check-in-input').value;
            const checkOutDate = document.querySelector('.check-out-input').value;
            const location_id = document.querySelector('[name=location_id]').value;
            const location_name = document.querySelector('[name=location_name]').value;
            const adult_number = document.querySelector('[name=adult_number]').value;
            const child_number = document.querySelector('[name=child_number]').value;
            const currency = document.querySelector("#dropdown-currency").innerText.trim();
            
            let dateRange = `${checkInDate} 12:00 am-${checkOutDate} 11:59 pm`;
            
            let url = `https://staging.balkanea.com/wp-plugin/APIs/filter_hotel.php?location_id=${encodeURIComponent(location_id)}&start=${encodeURIComponent(formatDate(checkInDate))}&end=${encodeURIComponent(formatDate(checkOutDate))}&adults=${adult_number}&children=${child_number}&currency=${encodeURIComponent(currency)}&security=${nonce}`;
        
            let redirectUrl = `https://staging.balkanea.com/hotel-search-popup-map/?location_name=${encodeURIComponent(location_name)}&location_id=${encodeURIComponent(location_id)}&start=${encodeURIComponent(checkInDate)}&end=${encodeURIComponent(checkOutDate)}&date=${encodeURIComponent(dateRange)}&room_num_search=1&adult_number=${encodeURIComponent(adult_number)}&child_number=${encodeURIComponent(child_number)}`;
        
                fetch(url, { 
                    method: 'GET',
                    keepalive: true 
                })
                .then(response => response.json())
                .then(data => {
                    searchButtonHome.querySelector('span').classList.remove('fa', 'fa-spinner', 'fa-spin');
                    searchButtonHome.querySelector('span').classList.add("stt-icon", "stt-icon-search-normal");
                })
                .catch(error => {
                    console.error('Error:', error);
                });
                
                window.location.href = redirectUrl;
        });
    }
    if (dropdown){
        dropdown.addEventListener('click', () => {
            const date = document.querySelector("#nav-st_hotel > div > div > div > form > div.form-group.form-date-field.form-date-search.d-flex.align-items-center > div.date-item-wrapper.d-flex.align-items-center.checkin > div > div");
            if (date)
                var date_start = date.innerText.split('/');

            if (parseInt(date_start[0]) >= date_now.getDate() && 
                parseInt(date_start[1]) >= (date_now.getMonth() + 1) && 
                parseInt(date_start[2]) >= date_now.getFullYear()){
                    searchButtonHome.disabled = false;
                            
            }else{
                searchButtonHome.disabled = true;
                const calendar_event_trigger = document.querySelector('.check-in-out');

                calendar_event_trigger.addEventListener('focusout', () => {
                    searchButtonHome.disabled = false;
                });

            }
        })
    }
    if (searchButton === null) return;
    
    let suggested_cards = document.querySelectorAll('.services-item.item-elementor');

    suggested_cards.forEach(card => {
    let link = card.querySelector('a:not([class])');
    console.log(link);
    if (link) {
        let originalHref = link.href;
        let modifiedHref = originalHref.includes('?') ? `${originalHref}&search=yes` : `${originalHref}?search=yes`;
        link.href = modifiedHref;
        
        let priceWrapper = card.querySelector('.price-wrapper');
        if (priceWrapper) {
            priceWrapper.innerText = '';
            
            let button = document.createElement('a');
            button.textContent = 'Check Prices';
            button.className = 'show-detail btn-v2 btn-primary custom-button-nikola';
            button.setAttribute('href', modifiedHref)

            priceWrapper.appendChild(button);
        }
    }
    });

    searchButton.addEventListener('submit', (e) => {
        e.preventDefault();

        let check_in = document.querySelector("#st-list-room > div.st-list-rooms.relative > div.fetch > div > form > input[type=hidden]:nth-child(1)");

        console.log(check_in);
    })
});
