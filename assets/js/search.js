(function() {
  document.addEventListener('DOMContentLoaded', function() {
    
    const translations = window.searchTranslations || {};
    const noResultsText = translations.no_results_found || 'ไม่พบผลลัพธ์';
    const priceOnRequestText = translations.price_on_request || 'ราคาตามตกลง';

    performFilterSearch({});

    function performFilterSearch(filters) {
      const params = new URLSearchParams(filters);
      fetch('search_rent_place.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString()
      })
      .then(res => res.json())
      .then(displaySearchResults)
      .catch(error => {
        console.error('Fetch Error:', error);
        document.querySelector('.search-results-info').innerHTML = `<p class="text-center text-danger">เกิดข้อผิดพลาดในการโหลดข้อมูล</p>`;
      });
    }

    function displaySearchResults(results) {
      const container = document.querySelector('.search-results-info');
      container.innerHTML = '';

      if (results.length === 0) {
        container.innerHTML = `<p class="text-center text-muted">${noResultsText}</p>`;
        return;
      }

      results.forEach(item => {
        const col = document.createElement('div');
        col.className = 'col-12 col-sm-6 col-md-4';

        const card = document.createElement('div');
        card.className = 'card h-100';
        card.style.cursor = 'pointer';
        card.addEventListener('click', () => {
          window.open(
            `rent_place.php?id=${encodeURIComponent(item.id)}&name=${encodeURIComponent(item.rp_name)}`,
            '_blank'
          );
        });

        const img = document.createElement('img');
        // จุดแสดงรูปภาพ (ถูกต้องแล้ว)
        img.src = item.attach_name ? 'assets/rent_place/' + item.attach_name : 'assets/img/properties/property-1.jpg';
        img.className = 'card-img-top';
        img.alt = item.rp_name;
        card.appendChild(img);

        const body = document.createElement('div');
        body.className = 'card-body d-flex flex-column';

        const title = document.createElement('h5');
        title.className = 'card-title';
        // จุดแสดงชื่อที่แปลแล้ว (ถูกต้องแล้ว)
        title.textContent = item.rp_name;
        body.appendChild(title);

        const loc = document.createElement('p');
        loc.className = 'card-text text-muted mb-2';
        // จุดแสดงที่อยู่ที่แปลแล้ว (ถูกต้องแล้ว)
        loc.textContent = `${item.sub_district_name}, ${item.district_name}, ${item.province_name}`;
        body.appendChild(loc);

        const price = document.createElement('p');
        price.className = 'card-text fw-bold mb-2';
        price.textContent = item.price === "0.00"
          ? priceOnRequestText
          : `฿${Number(item.price).toLocaleString()}`;
        body.appendChild(price);

        const meta = document.createElement('p');
        meta.className = 'card-text text-secondary mt-auto';
        // จุดแสดงข้อมูลที่แปลแล้ว (ถูกต้องแล้ว)
        meta.innerHTML = `
          <i class="fa fa-bed"></i> ${item.translated_bedrooms}
          &nbsp;|&nbsp;
          <i class="fa fa-bath"></i> ${item.translated_bathrooms}
          &nbsp;|&nbsp;
          ${item.translated_size}
        `;
        body.appendChild(meta);

        card.appendChild(body);
        col.appendChild(card);
        container.appendChild(col);
      });
    }

    // --- Event Listeners (โค้ดเดิมของคุณ) ---
    document.getElementById('searchButton').addEventListener('click', () => {
      const term = document.getElementById('searchInput').value.trim();
      performFilterSearch({ searchTerm: term });
    });
    // ... โค้ดที่เหลือเหมือนเดิม ...
  });
})();