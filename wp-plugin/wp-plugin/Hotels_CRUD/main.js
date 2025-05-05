    document.addEventListener('DOMContentLoaded', function() {
        fetch('https://staging.balkanea.com/wp-plugin/Hotels_CRUD/fetch_hotels.php')
            .then(response => response.text())
            .then(data => {
                document.querySelector("#st-content-wrapper > div.container").innerHTML = data;
            })
            .catch(error => console.error('Error fetching hotel data:', error));
            
        document.querySelectorAll(".btn-danger").forEach(item => {
            console.log(item);
            item.addEventListener('click', (e) => {
                console.log(e);
            });
        });
    });