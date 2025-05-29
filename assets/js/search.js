(function() {
  document.addEventListener('DOMContentLoaded', function() {
    // เรียก load ผลลัพธ์ครั้งแรก (ไม่มีเงื่อนไข)
    performFilterSearch({});

    // ฟังก์ชันค้นหาโดยส่ง filter object
    function performFilterSearch(filters) {
      const params = new URLSearchParams(filters);
      fetch('search_rent_place.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString()
      })
      .then(res => res.json())
      .then(displaySearchResults)
      .catch(console.error);
    }

    // ฟังก์ชันแสดงผลลัพธ์
    function displaySearchResults(results) {
      const container = document.querySelector('.search-results-info');
      container.innerHTML = '';

      if (results.length === 0) {
        container.innerHTML = '<p class="text-center text-muted">ไม่พบผลลัพธ์</p>';
        return;
      }

      results.forEach(item => {
        // 1) สร้าง wrapper col
        const col = document.createElement('div');
        col.className = 'col-12 col-sm-6 col-md-4';

        // 2) สร้าง card
        const card = document.createElement('div');
        card.className = 'card h-100';
        card.style.cursor = 'pointer';
        card.addEventListener('click', () => {
          window.open(
            `rent_place.php?id=${encodeURIComponent(item.id)}&name=${encodeURIComponent(item.rp_name)}`,
            '_blank'
          );
        });

        // รูปบนหัว card
        const img = document.createElement('img');
        img.src = 'assets/rent_place/' + item.attach_name;
        img.className = 'card-img-top';
        img.alt = item.rp_name;
        card.appendChild(img);

        // body
        const body = document.createElement('div');
        body.className = 'card-body d-flex flex-column';

        // ชื่อ
        const title = document.createElement('h5');
        title.className = 'card-title';
        title.textContent = item.rp_name;
        body.appendChild(title);

        // ที่อยู่
        const loc = document.createElement('p');
        loc.className = 'card-text text-muted mb-2';
        loc.textContent = `${item.sub_district_name}, ${item.district_name}, ${item.province_name}`;
        body.appendChild(loc);

        // ราคา
        const price = document.createElement('p');
        price.className = 'card-text fw-bold mb-2';
        price.textContent = item.price === "0.00"
          ? 'ราคาตามตกลง'
          : `฿${Number(item.price).toLocaleString()}`;
        body.appendChild(price);

        // meta
        const meta = document.createElement('p');
        meta.className = 'card-text text-secondary mt-auto';
        meta.innerHTML = `
          <i class="fa fa-bed"></i> ${item.room_qty}
          &nbsp;|&nbsp;
          <i class="fa fa-bath"></i> ${item.toilet_qty}
          &nbsp;|&nbsp;
          ${item.size} ตร.ม.
        `;
        body.appendChild(meta);

        card.appendChild(body);
        col.appendChild(card);
        container.appendChild(col);
      });
    }

    // ปุ่มค้นหา
    document.getElementById('searchButton').addEventListener('click', () => {
      const term = document.getElementById('searchInput').value.trim();
      performFilterSearch({ searchTerm: term });
    });

    // Enter key
    document.getElementById('searchInput').addEventListener('keyup', e => {
      if (e.key === 'Enter') {
        document.getElementById('searchButton').click();
      }
    });

    // ปุ่มเคลียร์
    document.querySelector('.clear-button').addEventListener('click', () => {
      document.getElementById('searchInput').value = '';
      performFilterSearch({});
    });

    // ปุ่มปรับเงื่อนไข
    document.querySelector('#filterModal .btn-primary').addEventListener('click', () => {
      const filters = {
        type: document.getElementById('type').value,
        minPrice: document.getElementById('minPrice').value,
        maxPrice: document.getElementById('maxPrice').value,
        roomQty: document.getElementById('roomQty').value,
        minSize: document.getElementById('minSize').value,
        maxSize: document.getElementById('maxSize').value,
        distance: document.getElementById('distance').value,
        toiletQty: document.getElementById('toiletQty').value,
        // ดึง selected features (ถ้ามี)
        feature: getSelected('rentFacilitiesCombo'),
        facility: getSelected('rentFacilitiesFCombo')
      };
      performFilterSearch(filters);
      bootstrap.Modal.getInstance(document.getElementById('filterModal')).hide();
    });

    function getSelected(id) {
      return Array.from(document.querySelectorAll(`#${id} .feature-button.selected`))
                  .map(btn => btn.dataset.id)
                  .join(',');
    }
  });
})();
