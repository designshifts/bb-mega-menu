(function () {
  const menuToggle = document.querySelector(".main-menu-toggle");
  const menuLinks = document.querySelectorAll("li.has-mega-menu > button");

  if (!menuLinks.length) {
    return;
  }

  function setMenuHidden(menu, isHidden) {
    if (!menu) {
      return;
    }
    if (isHidden) {
      menu.setAttribute("hidden", "");
      menu.setAttribute("inert", "");
    } else {
      menu.removeAttribute("hidden");
      menu.removeAttribute("inert");
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    const allMegaMenus = document.querySelectorAll(".mega-menu");
    allMegaMenus.forEach(function (menu) {
      if (!menu.classList.contains("is-visible")) {
        setMenuHidden(menu, true);
      }
    });
  });

  function updateMenuStatus(message) {
    const menuStatus = document.getElementById("menu-status");
    if (menuStatus) {
      menuStatus.textContent = message;
    }
  }

  function closeMegaMenus() {
    const megaMenuItems = document.querySelectorAll("li.has-mega-menu");
    megaMenuItems.forEach(function (item) {
      item.classList.remove("has-mega-menu-open");
      const button = Array.from(item.children).find(function (child) {
        return child.tagName === "BUTTON";
      });
      if (button) {
        button.setAttribute("aria-expanded", "false");
      }
    });

    const megaMenus = document.querySelectorAll(".mega-menu");
    megaMenus.forEach(function (menu) {
      menu.classList.remove("is-visible");
      setMenuHidden(menu, true);
    });

    const menuMain = document.getElementById("menu-main");
    const menuMainFr = document.getElementById("menu-main-fr");
    if (menuMain) menuMain.classList.remove("mega-menu-open");
    if (menuMainFr) menuMainFr.classList.remove("mega-menu-open");

    updateMenuStatus("Menu closed");
    const openButton = document.querySelector(
      'li.has-mega-menu > button[aria-expanded="true"]'
    );
    if (openButton) {
      openButton.focus();
    } else if (menuToggle) {
      menuToggle.focus();
    }
  }

  menuLinks.forEach(function (link) {
    link.addEventListener("click", function () {
      const trigger = this;
      const parent = trigger.parentElement;
      const megaMenu = parent ? parent.querySelector(".mega-menu") : null;
      const isOpen = parent && parent.classList.contains("has-mega-menu-open");
      const menuLabel = trigger.textContent.trim().replace(/\s+/g, " ");
      const isMobile = window.matchMedia("(max-width: 1024px)").matches;
      const clickAction = isMobile
        ? "Main Menu Mobile drop down"
        : "Main Menu Desktop drop down";

      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push({
        event: "click",
        click_action: clickAction,
        click_value: menuLabel,
      });

      closeMegaMenus();

      if (!isOpen && parent && megaMenu) {
        parent.classList.add("has-mega-menu-open");
        megaMenu.classList.add("is-visible");
        setMenuHidden(megaMenu, false);

        const menuMain = document.getElementById("menu-main");
        const menuMainFr = document.getElementById("menu-main-fr");
        if (menuMain) menuMain.classList.add("mega-menu-open");
        if (menuMainFr) menuMainFr.classList.add("mega-menu-open");

        trigger.setAttribute("aria-expanded", "true");

        const focusableSelectors =
          'a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])';
        const firstFocusable = megaMenu.querySelector(focusableSelectors);
        if (firstFocusable) {
          firstFocusable.focus();
        }
        updateMenuStatus("Menu opened");
      }
    });
  });

  document.body.addEventListener("click", function (e) {
    const link = e.target.closest(".mega-menu a");
    if (!link) return;

    const linkLabel = link.textContent.trim();
    const isMobile = window.matchMedia("(max-width: 1024px)").matches;
    const clickAction = isMobile ? "Main Menu Mobile click" : "Mega Menu inner click";

    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
      event: "click",
      click_action: clickAction,
      click_value: linkLabel,
    });
  });

  window.addEventListener("scroll", closeMegaMenus);

  document.addEventListener("click", function (e) {
    if (!e.target.closest("li.has-mega-menu")) {
      closeMegaMenus();
    }
  });

  document.body.addEventListener("keydown", function (e) {
    const target = e.target.closest(".has-mega-menu > button");
    if (!target) return;
    if (e.key === "Enter" || e.key === " ") {
      e.preventDefault();
      target.click();
    }
  });

  document.addEventListener("keydown", function (e) {
    const focused = document.activeElement;
    const megaMenu = focused && focused.closest(".mega-menu");
    const triggerButton = focused && focused.closest(".has-mega-menu > button");

    if (e.key === "Escape") {
      const openMenu = document.querySelector(".mega-menu.is-visible");
      if (openMenu) {
        e.preventDefault();
        closeMegaMenus();
      }
      return;
    }

    if (megaMenu) {
      const focusableSelectors =
        'a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])';
      const focusableElements = Array.from(
        megaMenu.querySelectorAll(focusableSelectors)
      );
      const index = focusableElements.indexOf(focused);

      if (e.key === "ArrowDown") {
        e.preventDefault();
        const next = focusableElements[index + 1] || focusableElements[0];
        if (next) next.focus();
      }

      if (e.key === "ArrowUp") {
        e.preventDefault();
        const prev =
          focusableElements[index - 1] ||
          focusableElements[focusableElements.length - 1];
        if (prev) prev.focus();
      }

      if (e.key === "Tab") {
        if (e.shiftKey && index === 0) {
          e.preventDefault();
          focusableElements[focusableElements.length - 1].focus();
        } else if (!e.shiftKey && index === focusableElements.length - 1) {
          e.preventDefault();
          focusableElements[0].focus();
        }
      }
    }

    if (triggerButton) {
      const allTriggers = Array.from(
        document.querySelectorAll("li.has-mega-menu > button")
      );
      const currentIndex = allTriggers.indexOf(triggerButton);

      if (e.key === "ArrowRight" || e.key === "ArrowDown") {
        e.preventDefault();
        const nextTrigger = allTriggers[currentIndex + 1];
        if (nextTrigger) {
          nextTrigger.focus();
        }
      }

      if (e.key === "ArrowLeft" || e.key === "ArrowUp") {
        e.preventDefault();
        const prevTrigger = allTriggers[currentIndex - 1];
        if (prevTrigger) {
          prevTrigger.focus();
        }
      }
    }
  });

  document.addEventListener("click", function (e) {
    const megaMenuLink = e.target.closest(".wp-block-group.mega-menu-link");
    if (!megaMenuLink) return;

    if (e.target.tagName === "A" || e.target.closest("a")) {
      return;
    }

    const anchor = megaMenuLink.querySelector("a");
    if (!anchor) {
      return;
    }

    const href = anchor.getAttribute("href");
    const target = (anchor.getAttribute("target") || "").toLowerCase();

    if (target === "_blank") {
      window.open(href, "_blank", "noopener");
    } else {
      window.location.href = href;
    }
  });
})();
