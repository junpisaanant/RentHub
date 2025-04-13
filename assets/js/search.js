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
                const name = document.querySelector('#filterModal select:nth-child(1)').value;
                const propertyType = document.querySelector('#filterModal select:nth-child(2)').value;
                const minPrice = document.querySelector('#filterModal .price-range input:nth-child(1)').value;
                const maxPrice = document.querySelector('#filterModal .price-range input:nth-child(2)').value;
                const bedrooms = document.querySelector('#filterModal select:nth-child(4)').value;
                const minArea = document.querySelector('#filterModal .area-range input:nth-child(1)').value;
                const maxArea = document.querySelector('#filterModal .area-range input:nth-child(2)').value;
                const stationDistance = document.querySelector('#filterModal select:nth-child(6)').value;
                const minPricePerSqm = document.querySelector('#filterModal .price-per-sqm-range input:nth-child(1)').value;
                const maxPricePerSqm = document.querySelector('#filterModal .price-per-sqm-range input:nth-child(2)').value;
                const bathrooms = document.querySelector('#filterModal select:nth-child(8)').value;
                const ownership = document.querySelector('#filterModal select:nth-child(9)').value;
                const selectedFeatureIds = getSelectedFeatureIds('rentFacilitiesCombo');

                // Log ค่า (เพื่อตรวจสอบ)
                console.log("ชื่อ:", name);
                console.log("ประเภทอสังหาฯ:", propertyType);
                console.log("ราคาต่ำสุด:", minPrice);
                console.log("ราคาสูงสุด:", maxPrice);
                console.log("จำนวนห้องนอน:", bedrooms);
                console.log("พื้นที่ต่ำสุด:", minArea);
                console.log("พื้นที่สูงสุด:", maxArea);
                console.log("ระยะจากสถานีไฟฟ้า:", stationDistance);
                console.log("ราคาต่อตร.ม. ต่ำสุด:", minPricePerSqm);
                console.log("ราคาต่อตร.ม. สูงสุด:", maxPricePerSqm);
                console.log("จำนวนห้องน้ำ:", bathrooms);
                console.log("การถือครอง:", ownership);
                console.log("จุดเด่น:", feature);

                // เรียกใช้ฟังก์ชันค้นหา (ปรับตามการใช้งานจริงของคุณ)
                performFilterSearch({ 
                    name, 
                    propertyType, 
                    minPrice, 
                    maxPrice, 
                    bedrooms, 
                    minArea, 
                    maxArea, 
                    stationDistance, 
                    minPricePerSqm, 
                    maxPricePerSqm, 
                    bathrooms, 
                    ownership, 
                    feature: selectedFeatureIds.join(',')  // ส่งเป็น string คั่นด้วย comma
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