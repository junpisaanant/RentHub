(function() {

    document.addEventListener('DOMContentLoaded', function() {

        // หลังจากตั้งค่าทุกอย่างแล้ว ให้เรียกค้นหาอัตโนมัติเพื่อแสดงผลทั้งหมด
        performFilterSearch({});
        // ฟังก์ชันในการแสดงผลลัพธ์การค้นหา
        function performFilterSearch(filters) {
            let formData = new URLSearchParams();
            for (let key in filters) {
                formData.append(key, filters[key]);
            }
            fetch('search_rent_place.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: formData.toString()
            })
            .then(response => response.json())
            .then(data => {
                // แสดงผลลัพธ์ที่ได้รับจาก server (การ์ด หรือข้อความ "ไม่พบผลลัพธ์")
                displaySearchResults(data);
            })
            .catch(error => console.error('Error:', error));
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
        
        //ปุ่ม clear
        document.querySelector('.clear-button').addEventListener('click', function() {
            document.querySelector('#searchInput').value = '';
            // เรียกค้นหาเมื่อเคลียร์ช่องค้นหา
            performSearch();
        });

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

        // ฟังก์ชันแสดงผลลัพธ์การค้นหา
        function displaySearchResults(results) {
            let resultsDiv = document.querySelector('.search-results-info');
            if (resultsDiv) {
                // เคลียร์ของเดิม
                resultsDiv.innerHTML = '';
                if (results.length > 0) {
                    // วน loop ใน results สร้างการ์ด
                    results.forEach(item => {
                        // ตัวอย่าง field จากฐานข้อมูล: rp_name, province_name, district_name, sub_district_name, price, room_qty, toilet_qty, near_rail, property_type, create_datetime
                        let card = document.createElement('div');
                        card.className = 'property-card';

                        // เพิ่ม event listener เมื่อคลิกที่การ์ด
                        card.addEventListener('click', function() {
                            // ส่ง id และ rp_name ใน URL query parameter
                            window.location.href = "about.php?id=" + encodeURIComponent(item.id) + "&name=" + encodeURIComponent(item.rp_name);
                        });

                        // สมมุติว่าคุณมีรูปหรือมี URL ในฐานข้อมูลชื่อว่า item.image_url ถ้าไม่มีก็ใช้รูป placeholder
                        let image_url = 'assets/rent_place/'+item.attach_name + '/1.jpg';

                        // (1) ส่วนซ้าย รูปภาพ
                        let imageDiv = document.createElement('div');
                        imageDiv.className = 'property-image';
                        let img = document.createElement('img');
                        img.src = image_url;
                        imageDiv.appendChild(img);

                         // **เพิ่มส่วนแสดงจำนวนภาพ**
                        let imageCount = item.place_cnt || 0;  // ตรวจสอบฟิลด์ image_count จากฐานข้อมูล หรือให้ค่า default
                        let imageCountDiv = document.createElement('div');
                        imageCountDiv.className = 'image-count';
                        imageCountDiv.innerHTML = '<i class="fa fa-image"></i> ' + imageCount;
                        imageDiv.appendChild(imageCountDiv);

                        // ป้ายผู้พัฒนาโครงการ (ถ้ามีข้อมูล dev_name หรือ developer)
                        /*let developerLabel = document.createElement('span');
                        developerLabel.className = 'developer-label';
                        developerLabel.textContent = item.developer_name || 'ผู้พัฒนาโครงการ';
                        imageDiv.appendChild(developerLabel);*/

                        // (2) ส่วนขวา รายละเอียด
                        let detailsDiv = document.createElement('div');
                        detailsDiv.className = 'property-details';

                        // ชื่อประกาศ
                        let titleEl = document.createElement('h3');
                        titleEl.className = 'property-title';
                        titleEl.textContent = item.rp_name || 'ไม่ทราบชื่อ';
                        detailsDiv.appendChild(titleEl);

                        // ที่อยู่ (เช่น ซอย, อำเภอ, จังหวัด)
                        let locationEl = document.createElement('p');
                        locationEl.className = 'property-location';
                        locationEl.textContent = `${item.sub_district_name || ''}, ${item.district_name || ''}, ${item.province_name || ''}`;
                        detailsDiv.appendChild(locationEl);

                        // ราคา
                        let priceEl = document.createElement('div');
                        priceEl.className = 'property-price';
                        let formattedPrice = Number(item.price).toLocaleString('en-US');
                        priceEl.textContent = `฿${formattedPrice || 0}`;
                        detailsDiv.appendChild(priceEl);

                        // เมตา: เช่น ห้องนอน, ขนาด, ราคา/ตร.ม. ฯลฯ
                        let metaEl = document.createElement('div');
                        metaEl.className = 'property-meta';
                        // สมมุติว่า item.size มีขนาด, item.price_per_sqm คือราคาต่อตร.ม.
                        let bedText = `${item.room_qty || 0} ห้องนอน`;
                        let sizeText = `${item.size || 0} ตร.ม.`;
                        let toiletText = `${item.toilet_qty || 0} ห้องน้ำ`;
                        metaEl.innerHTML = `${bedText} <span>|</span> ${toiletText} <span>|</span> ${sizeText}`;
                        detailsDiv.appendChild(metaEl);

                        // ข้อมูลอื่น ๆ (ปีที่สร้างเสร็จ, วันเวลาที่ลงประกาศ)
                        let addInfo = document.createElement('div');
                        addInfo.className = 'property-additional-info';
                        // สมมุติว่า item.year_built และ item.post_date
                        addInfo.innerHTML = `
                            <div>${item.property_type}</div>
                            <div>${item.near_rail}</div>
                        `;
                        detailsDiv.appendChild(addInfo);

                        // ประกอบร่าง
                        card.appendChild(imageDiv);
                        card.appendChild(detailsDiv);

                        resultsDiv.appendChild(card);
                    });
                } else {
                    resultsDiv.innerHTML = "<p>ไม่พบผลลัพธ์</p>";
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