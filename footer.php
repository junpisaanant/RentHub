<footer id="footer" class="footer light-background">

    <div class="container">
      <div class="row gy-3">
        <div class="col-lg-3 col-md-6 d-flex">
          <i class="bi bi-geo-alt icon"></i>
          <div class="address">
            <h4><?php echo $lang['address']; ?></h4>
            <p><?php echo $lang['address_detail_1']; ?></p>
            <p><?php echo $lang['address_detail_2']; ?></p>
            <p></p>
          </div>

        </div>

        <div class="col-lg-3 col-md-6 d-flex">
          <i class="bi bi-telephone icon"></i>
          <div>
            <h4><?php echo $lang['contact']; ?></h4>
            <p>
              <strong><?php echo $lang['phone']; ?></strong> <span>062-245-5942</span><br>
              <strong><?php echo $lang['email']; ?></strong> <span>n.juiprasert@gmail.com</span><br>
            </p>
          </div>
        </div>

        <div class="col-lg-3 col-md-6 d-flex">
          <i class="bi bi-clock icon"></i>
          <div>
            <h4><?php echo $lang['opening_hours']; ?></h4>
            <p>
              <strong><?php echo $lang['mon_sat']; ?></strong> <span><?php echo $lang['hours']; ?></span><br>
              <strong><?php echo $lang['sunday']; ?></strong>: <span><?php echo $lang['closed']; ?></span>
            </p>
          </div>
        </div>

        <div class="col-lg-3 col-md-6">
          <h4><?php echo $lang['follow_us']; ?></h4>
          <div class="social-links d-flex">
            <a href="https://www.facebook.com/profile.php?id=61573999664508" class="facebook" target="_blank"><i class="bi bi-facebook"></i></a>
            <a href="https://www.instagram.com/the_prestigeliving_sathorn?igsh=MTlvZDRvYnR2dHkycg==" class="instagram" target="_blank"><i class="bi bi-instagram"></i></a>
            <a href="#" class="line" data-bs-toggle="modal" data-bs-target="#lineModal"><i class="bi bi-line"></i></a>
            <a href="#" class="whatsapp" data-bs-toggle="modal" data-bs-target="#whatsappModal"><i class="bi bi-whatsapp"></i></a>
            <a href="#" class="wechat" data-bs-toggle="modal" data-bs-target="#wechatModal"><i class="bi bi-wechat"></i></a>
          </div>
        </div>

      </div>
    </div>

    <div class="container copyright text-center mt-4">
      <p>© <span><?php echo $lang['copyright']; ?></span> <strong class="px-1 sitename">The Prestige Living</strong> <span><?php echo $lang['all_rights_reserved']; ?></span></p>
    </div>

    <div class="modal fade" id="lineModal" tabindex="-1" aria-labelledby="lineModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="lineModalLabel"><?php echo $lang['contact_us_line']; ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center">
          <img src="assets/img/line.jpg" alt="QR Code Line" class="img-fluid" style="max-width: 200px;">
          <p><?php echo $lang['scan_qr_line']; ?></p>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="whatsappModal" tabindex="-1" aria-labelledby="whatsappModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="whatsappModalLabel"><?php echo $lang['contact_us_whatsapp']; ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center">
          <img src="assets/img/whatsapp.jpg" alt="QR Code Whatsapp" class="img-fluid" style="max-width: 200px;">
          <p><?php echo $lang['scan_qr_whatsapp']; ?></p>
        </div>
      </div>
    </div>
  </div>

<div class="modal fade" id="wechatModal" tabindex="-1" aria-labelledby="wechatModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="wechatModalLabel"><?php echo $lang['contact_us_wechat']; ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img src="assets/img/wechat.jpg" alt="QR Code Wechat" class="img-fluid" style="max-width: 200px;">
        <p><?php echo $lang['scan_qr_wechat']; ?></p>
      </div>
    </div>
  </div>
</div>

  </footer>
  <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <div id="preloader"></div>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>
  <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
  <script src="assets/vendor/purecounter/purecounter_vanilla.js"></script>

  <script src="assets/js/main.js"></script>

  <a href="https://wa.me/66622455942" class="whatsapp-button" target="_blank">
    <i class="bi bi-whatsapp"></i>
  </a>

</body>