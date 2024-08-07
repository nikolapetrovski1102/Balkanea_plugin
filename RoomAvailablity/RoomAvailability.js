    const searchButton = document.querySelector("#nav-book > div > div > div > div > div > div > form > div.submit-group.button-search-wrapper > button")

    if ( document.querySelector('.room-rates') ){
        document.querySelector('.room-rates').style.display = 'none';
        document.querySelector("#st-content-wrapper > div.container > div > div.row > div.col-12.col-lg-8 > div.st-attributes.st-section-single.stt-attr-room-facilities").style.display = 'none';
    }

    if (searchButton === null) throw ("Button not found");

    searchButton.type = 'button';
    searchButton.name = '';

    searchButton.addEventListener('submit', (e) => {
        e.preventDefault();

        let check_in = document.querySelector("#st-list-room > div.st-list-rooms.relative > div.fetch > div > form > input[type=hidden]:nth-child(1)");

        console.log(check_in);
    })