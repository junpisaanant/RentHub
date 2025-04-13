(function() {
    //ปุ่ม clear
    document.querySelector('.clear-button').addEventListener('click', function() {
        document.querySelector('#searchInput').value = '';
    });

    document.addEventListener('DOMContentLoaded', function() {
        // ฟังก์ชันในการแสดงผลลัพธ์การค้นหา
        function performFilterSearch(filters) {
            // แสดงผลลัพธ์การค้นหา (แทนที่ด้วย logic การค้นหาของคุณ)
            let resultsDiv = document.querySelector('.search-results-info');
            if (resultsDiv) {
              resultsDiv.innerHTML = `<p>ค้นหาด้วยตัวกรอง: ${JSON.stringify(filters)} </p>`;
            }
        }

        // ตัวอย่างการทำงานเมื่อคลิกปุ่ม "ปรับตามเงื่อนไข"
        function getSelectedFeatureIds(elementId) {
            const selectedButtons = document.querySelectorAll(`#${elementId} .feature-button.selected`);
            // เก็บค่า data-id ของปุ่มที่ถูกเลือกใน array
            return Array.from(selectedButtons).map(btn => btn.getAttribute('data-id'));
        }

        const applyButton = document.querySelector('#filterModal .btn-primary');
        if (applyButton) {
            applyButton.addEventListener('click', function() {
                // ดึงค่าจากฟอร์มใน Modal
                const type = document.getElementById("type").value;
                const minPrice = document.getElementById("minPrice").value;
                const maxPrice = document.getElementById("maxPrice").value;
                const roomQty = document.getElementById("roomQty").value;
                const minSize = document.getElementById("minSize").value;
                const maxSize = document.getElementById("maxSize").value;
                const distance = document.getElementById("distance").value;
                const toiletQty = document.getElementById("toiletQty").value;
                
                // ดึง id จากจุดเด่นและสิ่งอำนวยความสะดวกที่ถูกเลือก
                const selectedFeatureIds = getSelectedFeatureIds('rentFacilitiesCombo');
                const selectedFacilityIds = getSelectedFeatureIds('rentFacilitiesFCombo');

                // แปลง array เป็น string โดยใช้ comma เป็นตัวแบ่ง (หรือส่งเป็น array หาก server รองรับ)
                const feature = selectedFeatureIds.join(',');
                const facility = selectedFacilityIds.join(',');

                // เรียกใช้ฟังก์ชันค้นหา (ปรับตามการใช้งานจริงของคุณ)
                performFilterSearch({ 
                    type,
                    minPrice,
                    maxPrice,
                    roomQty,
                    minSize,
                    maxSize,
                    distance,
                    toiletQty,
                    feature,
                    facility
                });

                // ปิด Modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('filterModal'));
                modal.hide();

            });
        }

        //ปุ่มค้นหา
        let searchButton = document.querySelector('#searchButton');
        if (searchButton) {
            searchButton.addEventListener('click', performSearch);
        }

        document.getElementById('searchInput').addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                performSearch();
            }
        });

        // ฟังก์ชันค้นหา
        function performSearch() {
            let searchTerm = document.querySelector('#searchInput').value;
            if (searchTerm) {
                // ส่งคำขอ AJAX ไปยังเซิร์ฟเวอร์ PHP
                fetch('search_rent_place.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'searchTerm=' + encodeURIComponent(searchTerm)
                })
                .then(response => response.json())
                .then(data => {
                    // แสดงผลลัพธ์การค้นหา
                    displaySearchResults(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        }

        // ฟังก์ชันแสดงผลลัพธ์การค้นหา
        function displaySearchResults(results) {
            let resultsDiv = document.querySelector('.search-results-info');
            if (resultsDiv) {
                if (results.length > 0){
                    let html = results.map(item => `<p>Name: ${item.name}</p>`).join('');
                    resultsDiv.innerHTML = html
                } else {
                    resultsDiv.innerHTML = "<p>ไม่พบผลลัพธ์</p>"
                }
            }
        }

        // ดึงและแสดง จุดเด่น
        // สร้างตัวเลือก จุดเด่น หรือ สิ่งอำนวยความสะดวก
        function populateFeatures(features, elementId) {
            const featureContainer = document.querySelector(`#${elementId}`);
            if (featureContainer) {
                // เคลียร์เนื้อหาก่อนสร้างใหม่
                featureContainer.innerHTML = '';
                features.forEach(feature => {
                    const button = document.createElement('button');
                    // แสดงชือจุดเด่น
                    button.textContent = feature.name;
                    button.className = 'feature-button';
                    // เพิ่ม attribute data-id เพื่อเก็บค่า id ของจุดเด่น
                    button.setAttribute('data-id', feature.id);
        
                    button.addEventListener('click', function() {
                        // Toggle class 'selected' เพื่อแสดงสถานะที่เลือก
                        this.classList.toggle('selected');
                    });
        
                    featureContainer.appendChild(button);
                });
            }
        }

        // ดึงและแสดง จุดเด่น
        fetch('rent_facilities_combo.php', {
            method: 'GET'
        })
        .then(response => response.json())
        .then(data => {
            populateFeatures(data.features, 'rentFacilitiesCombo');
        })
        .catch(error => {
            console.error('Error:', error);
        });

        // ดึงและแสดง สิ่งอำนวยความสะดวก
        fetch('rent_facilitiesF_combo.php', {
            method: 'GET'
        })
        .then(response => response.json())
        .then(data => {
            populateFeatures(data.features, 'rentFacilitiesFCombo');
        })
        .catch(error => {
            console.error('Error:', error);
        });

    });

})();