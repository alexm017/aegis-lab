(function () {
  const navToggle = document.getElementById("nav-toggle");
  const primaryNav = document.getElementById("primary-nav");
  const navLinks = primaryNav ? Array.from(primaryNav.querySelectorAll("a")) : [];
  const revealNodes = document.querySelectorAll(".reveal");
  const yearEl = document.getElementById("year");

  if (yearEl) {
    yearEl.textContent = new Date().getFullYear();
  }

  if (navToggle && primaryNav) {
    navToggle.addEventListener("click", function () {
      const expanded = navToggle.getAttribute("aria-expanded") === "true";
      navToggle.setAttribute("aria-expanded", String(!expanded));
      primaryNav.classList.toggle("open", !expanded);
    });

    navLinks.forEach(function (link) {
      link.addEventListener("click", function () {
        if (window.matchMedia("(max-width: 860px)").matches) {
          navToggle.setAttribute("aria-expanded", "false");
          primaryNav.classList.remove("open");
        }
      });
    });
  }

  if (navLinks.length) {
    const sectionLinks = navLinks
      .map(function (link) {
        const id = link.getAttribute("href");
        if (!id || !id.startsWith("#")) {
          return null;
        }
        const section = document.querySelector(id);
        return section ? { link: link, section: section } : null;
      })
      .filter(Boolean);

    const setActiveLink = function (activeId) {
      navLinks.forEach(function (link) {
        const isActive = link.getAttribute("href") === "#" + activeId;
        link.classList.toggle("active", isActive);
      });
    };

    const navObserver = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            setActiveLink(entry.target.id);
          }
        });
      },
      { threshold: 0.45, rootMargin: "-25% 0px -45% 0px" }
    );

    sectionLinks.forEach(function (item) {
      navObserver.observe(item.section);
    });
  }

  const revealObserver = new IntersectionObserver(
    function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add("visible");
          revealObserver.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.15 }
  );

  revealNodes.forEach(function (node) {
    revealObserver.observe(node);
  });
})();
