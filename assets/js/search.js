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
      const row = document.querySelector('.search-results-info');
      row.innerHTML = '';

      if (!results.length) {
        row.innerHTML = '<p class="text-center text-muted">ไม่พบผลลัพธ์</p>';
        return;
      }

      results.forEach(item => {
        const col = document.createElement('div');
        col.className = 'col-12 col-md-6 col-lg-4';

        const card = document.createElement('div');
        card.className = 'card h-100';
        // … สร้างรูป, body, meta …
        col.appendChild(card);
        row.appendChild(col);
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
