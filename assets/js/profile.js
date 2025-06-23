
    // Gender toggle
    function selectGender(gender) {
      const buttons = document.querySelectorAll('.gender-btn');
      buttons.forEach(btn => btn.classList.remove('active'));
      if (gender === 'male') {
        buttons[0].classList.add('active');
      } else {
        buttons[1].classList.add('active');
      }
    }

    // Avatar upload preview
    document.getElementById("avatarUpload").addEventListener("change", function () {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
          document.getElementById("avatarPreview").src = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    });

    // SPA navigation logic
    document.addEventListener("DOMContentLoaded", () => {
      const menuItems = document.querySelectorAll(".sidebar ul li");
      const sections = document.querySelectorAll(".tab-content");

      menuItems.forEach(item => {
        item.addEventListener("click", () => {
          // Activate menu
          menuItems.forEach(el => el.classList.remove("active"));
          item.classList.add("active");

          // Show relevant section
          const targetId = item.getAttribute("data-section");
          sections.forEach(section => {
            section.classList.remove("active");
            if (section.id === targetId) {
              section.classList.add("active");
            }
          });
        });
      });
    });
  