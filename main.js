(function () {
  function setCurrentYear() {
    var yearNode = document.getElementById("year");
    if (yearNode) {
      yearNode.textContent = new Date().getFullYear();
    }
  }

  function setupNavSelection() {
    var navLinks = document.querySelectorAll(".site-header nav a");
    if (!navLinks.length) {
      return;
    }

    function clearSelected() {
      for (var i = 0; i < navLinks.length; i += 1) {
        navLinks[i].classList.remove("nav-selected");
      }
    }

    function applyHashSelection() {
      var hash = window.location.hash;
      if (!hash) {
        return;
      }

      for (var i = 0; i < navLinks.length; i += 1) {
        var href = navLinks[i].getAttribute("href");
        if (href === hash || href === "/" + hash) {
          clearSelected();
          navLinks[i].classList.add("nav-selected");
          break;
        }
      }
    }

    for (var i = 0; i < navLinks.length; i += 1) {
      (function (link) {
        var href = link.getAttribute("href") || "";
        if (href.charAt(0) === "#") {
          link.addEventListener("click", function () {
            clearSelected();
            link.classList.add("nav-selected");
          });
        }
      })(navLinks[i]);
    }

    window.addEventListener("hashchange", applyHashSelection);
    applyHashSelection();
  }

  function setupMobileMenu() {
    var header = document.querySelector(".site-header");
    if (!header) {
      return;
    }

    var toggle = header.querySelector(".menu-toggle");
    var nav = header.querySelector("nav");
    if (!toggle || !nav) {
      return;
    }

    function setOpenState(isOpen) {
      if (isOpen) {
        header.classList.add("nav-open");
        toggle.setAttribute("aria-expanded", "true");
      } else {
        header.classList.remove("nav-open");
        toggle.setAttribute("aria-expanded", "false");
      }
    }

    toggle.addEventListener("click", function () {
      setOpenState(!header.classList.contains("nav-open"));
    });

    var navLinks = nav.querySelectorAll("a");
    for (var i = 0; i < navLinks.length; i += 1) {
      navLinks[i].addEventListener("click", function () {
        setOpenState(false);
      });
    }

    document.addEventListener("click", function (event) {
      if (!header.classList.contains("nav-open")) {
        return;
      }
      if (header.contains(event.target)) {
        return;
      }
      setOpenState(false);
    });

    window.addEventListener("resize", function () {
      if (window.innerWidth > 760) {
        setOpenState(false);
      }
    });
  }

  function createGoldenSandController() {
    if (!document.body.classList.contains("home-page")) {
      return null;
    }

    if (window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
      return null;
    }

    var heroSection = document.querySelector(".main-hero");
    var titleNode = document.querySelector(".hero-team-name");
    var subtitleNode = document.querySelector(".hero-subtitle");
    if (!heroSection || !titleNode || !subtitleNode) {
      return null;
    }

    var canvas = document.createElement("canvas");
    canvas.className = "golden-sand-canvas";
    document.body.appendChild(canvas);

    var ctx = canvas.getContext("2d");
    if (!ctx) {
      canvas.remove();
      return null;
    }

    var dpr = Math.min(window.devicePixelRatio || 1, 2);
    var width = 0;
    var height = 0;
    var particles = [];
    var rafId = 0;
    var lastTs = performance.now();
    var typingDone = false;
    var active = true;
    var cleaned = false;

    var palette = [
      [237, 187, 62],
      [224, 167, 36],
      [209, 151, 24],
      [194, 136, 20],
      [180, 123, 15],
    ];

    function rand(min, max) {
      return min + Math.random() * (max - min);
    }

    function randomColor() {
      return palette[Math.floor(Math.random() * palette.length)];
    }

    function resizeCanvas() {
      width = window.innerWidth;
      height = window.innerHeight;
      canvas.width = Math.floor(width * dpr);
      canvas.height = Math.floor(height * dpr);
      canvas.style.width = width + "px";
      canvas.style.height = height + "px";
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }

    function heroBottomY() {
      var r = heroSection.getBoundingClientRect();
      return r.bottom + 12;
    }

    function subtitleFlowZone() {
      var r = subtitleNode.getBoundingClientRect();
      return {
        left: r.left,
        right: r.right,
        top: r.top,
        bottom: r.bottom,
      };
    }

    function emitFromLetter(meta) {
      if (!active || !meta || !meta.target) {
        return;
      }

      var rect = meta.target.getBoundingClientRect();
      var progress = Math.max(0.06, Math.min(1, meta.index / Math.max(1, meta.total)));
      var spawnLift = 2.2;
      var xCenter = rect.left + rect.width * progress;
      var yCenter = rect.bottom - spawnLift;

      var textNode = meta.target.firstChild;
      if (textNode && textNode.nodeType === 3) {
        var charIndex = Math.max(0, Math.min(meta.index - 1, textNode.textContent.length - 1));
        try {
          var range = document.createRange();
          range.setStart(textNode, charIndex);
          range.setEnd(textNode, charIndex + 1);
          var charRect = range.getBoundingClientRect();
          if (charRect && charRect.width > 0 && charRect.height > 0) {
            xCenter = charRect.left + charRect.width * 0.5;
            yCenter = charRect.bottom - spawnLift;
          }
          range.detach();
        } catch (e) {
          // Fallback to proportional position when range measurement is unavailable.
        }
      }

      var amount = Math.floor(rand(4, 7));

      for (var i = 0; i < amount; i += 1) {
        particles.push({
          x: xCenter + rand(-11.5, 11.5),
          y: yCenter,
          vx: rand(-58, 58),
          vy: rand(65, 130),
          size: rand(1.2, 2.7),
          life: rand(3.6, 5.4),
          age: 0,
          color: randomColor(),
        });
      }

      if (!rafId) {
        lastTs = performance.now();
        rafId = requestAnimationFrame(tick);
      }
    }

    function updateParticles(dt) {
      var flow = subtitleFlowZone();
      var bottomLimit = heroBottomY();

      for (var i = particles.length - 1; i >= 0; i -= 1) {
        var p = particles[i];
        p.age += dt;
        if (p.age >= p.life) {
          particles.splice(i, 1);
          continue;
        }

        p.vy += 560 * dt;
        p.vx *= 0.995;

        if (
          p.x > flow.left &&
          p.x < flow.right &&
          p.y > flow.top - p.size &&
          p.y < flow.bottom
        ) {
          // Keep natural fall, with slight turbulence while crossing subtitle area.
          p.vx += rand(-16, 16) * dt;
          p.vy += 28 * dt;
        }

        p.x += p.vx * dt;
        p.y += p.vy * dt;

        if (p.y > bottomLimit || p.x < -40 || p.x > width + 40) {
          particles.splice(i, 1);
        }
      }
    }

    function drawParticles() {
      ctx.clearRect(0, 0, width, height);

      for (var i = 0; i < particles.length; i += 1) {
        var p = particles[i];
        var fade = 1 - p.age / p.life;
        if (fade <= 0) {
          continue;
        }

        var alpha = Math.min(1, fade * 0.95);
        var c = p.color;
        ctx.fillStyle = "rgba(" + c[0] + "," + c[1] + "," + c[2] + "," + alpha.toFixed(3) + ")";

        ctx.beginPath();
        ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
        ctx.fill();
      }
    }

    function cleanup() {
      if (cleaned) {
        return;
      }
      cleaned = true;
      if (rafId) {
        cancelAnimationFrame(rafId);
        rafId = 0;
      }
      window.removeEventListener("resize", resizeCanvas);
      canvas.remove();
    }

    function tick(ts) {
      var dt = Math.min((ts - lastTs) / 1000, 0.033);
      lastTs = ts;

      updateParticles(dt);
      drawParticles();

      if (typingDone && particles.length === 0) {
        cleanup();
        return;
      }

      rafId = requestAnimationFrame(tick);
    }

    resizeCanvas();
    window.addEventListener("resize", resizeCanvas);

    return {
      emitFromLetter: emitFromLetter,
      finish: function () {
        active = false;
        typingDone = true;
        if (!rafId && particles.length === 0) {
          cleanup();
        }
      },
    };
  }

  function runHeroTyping(sandController) {
    var typingTarget = document.querySelector(".hero-team-name[data-typing]");
    if (!typingTarget) {
      if (sandController && typeof sandController.finish === "function") {
        sandController.finish();
      }
      return;
    }

    var fullText = typingTarget.getAttribute("data-typing") || "Aegis Lab";
    var index = 0;
    var typeDelay = 160;

    typingTarget.textContent = "";
    typingTarget.classList.remove("typing-complete");

    function typeOnce() {
      index += 1;
      typingTarget.textContent = fullText.slice(0, index);

      var typedChar = fullText.charAt(index - 1);
      if (
        sandController &&
        typeof sandController.emitFromLetter === "function" &&
        typedChar.trim() !== ""
      ) {
        sandController.emitFromLetter({
          target: typingTarget,
          index: index,
          total: fullText.length,
        });
      }

      if (index >= fullText.length) {
        typingTarget.classList.add("typing-complete");
        if (sandController && typeof sandController.finish === "function") {
          sandController.finish();
        }
        return;
      }

      setTimeout(typeOnce, typeDelay);
    }

    setTimeout(typeOnce, 250);
  }

  function setupScrollHintAnimation() {
    var scrollHint = document.querySelector(".scroll-hint[href^=\"#\"]");
    if (!scrollHint) {
      return;
    }

    scrollHint.addEventListener("click", function (event) {
      var targetHash = scrollHint.getAttribute("href") || "";
      if (targetHash.charAt(0) !== "#") {
        return;
      }

      var targetNode = document.querySelector(targetHash);
      if (!targetNode) {
        return;
      }

      event.preventDefault();

      var startY = window.pageYOffset || document.documentElement.scrollTop || 0;
      var endY = startY + targetNode.getBoundingClientRect().top - 8;
      var distance = endY - startY;
      var startTs = performance.now();
      var duration = Math.max(680, Math.min(1250, Math.abs(distance) * 0.85));

      function easeInOutCubic(t) {
        if (t < 0.5) {
          return 4 * t * t * t;
        }
        return 1 - Math.pow(-2 * t + 2, 3) / 2;
      }

      function step(nowTs) {
        var progress = Math.min(1, (nowTs - startTs) / duration);
        var eased = easeInOutCubic(progress);
        window.scrollTo(0, startY + distance * eased);

        if (progress < 1) {
          requestAnimationFrame(step);
          return;
        }

        if (history && typeof history.replaceState === "function") {
          history.replaceState(null, "", targetHash);
        }
      }

      requestAnimationFrame(step);
    });
  }

  function setupViewportPing() {
    if (!document.body.classList.contains("home-page")) {
      return;
    }

    var sent = false;
    function buildPayload(reason) {
      var screenObj = window.screen || {};
      return {
        path: window.location && window.location.pathname ? window.location.pathname : "/",
        reason: reason || "load",
        vw: Math.round(window.innerWidth || 0),
        vh: Math.round(window.innerHeight || 0),
        sw: Math.round(screenObj.width || 0),
        sh: Math.round(screenObj.height || 0),
        dpr: Number(window.devicePixelRatio || 1),
      };
    }

    function sendPayload(reason) {
      if (sent) {
        return;
      }
      sent = true;

      var payload = buildPayload(reason);
      var body = JSON.stringify(payload);

      if (navigator.sendBeacon) {
        var blob = new Blob([body], { type: "application/json" });
        navigator.sendBeacon("/analytics-viewport", blob);
        return;
      }

      if (window.fetch) {
        fetch("/analytics-viewport", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: body,
          credentials: "same-origin",
          keepalive: true,
        }).catch(function () {});
      }
    }

    window.addEventListener("load", function () {
      setTimeout(function () {
        sendPayload("load");
      }, 120);
    });
  }

  setCurrentYear();
  setupNavSelection();
  setupMobileMenu();
  setupScrollHintAnimation();
  setupViewportPing();
  var sandController = createGoldenSandController();
  runHeroTyping(sandController);
})();
