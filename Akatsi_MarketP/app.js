// Akatsi Market Portal - Interactive Frontend Logic

document.addEventListener("DOMContentLoaded", () => {
  // --- Initialize Page Elements & Render Data ---
  initCountdown();
  renderPrices("all");
  filterDirectory(); // Initializes vendor directory and sets results counts
  renderTransportGuide();
  renderNewsAndUpdates();
  initMapInteractivity();
  initNavigation();
  initLanguage();
  initInquiryModal();

  // Price category filter tabs click handler
  const priceTabs = document.querySelectorAll(".price-filters .filter-tab");
  priceTabs.forEach(tab => {
    tab.addEventListener("click", (e) => {
      priceTabs.forEach(t => t.classList.remove("active"));
      e.target.classList.add("active");
      const category = e.target.getAttribute("data-price-cat");
      renderPrices(category);
      // Close the detail card when category shifts
      document.getElementById("price-detail-card").classList.add("hide");
    });
  });

  // Close price details click
  document.getElementById("close-price-details").addEventListener("click", () => {
    document.getElementById("price-detail-card").classList.add("hide");
    const activeCards = document.querySelectorAll(".price-card.active-card");
    activeCards.forEach(c => c.classList.remove("active-card"));
  });

  // Directory Filters Change Handlers
  document.getElementById("vendor-search-input").addEventListener("input", filterDirectory);
  document.getElementById("category-select").addEventListener("change", filterDirectory);
  document.getElementById("zone-select").addEventListener("change", filterDirectory);

  document.getElementById("reset-directory-filters").addEventListener("click", () => {
    document.getElementById("vendor-search-input").value = "";
    document.getElementById("category-select").value = "all";
    document.getElementById("zone-select").value = "all";
    filterDirectory();
  });
});

// --- 1. Countdown Logic ---
let countdownInterval;
function initCountdown() {
  updateCountdown();
  countdownInterval = setInterval(updateCountdown, 1000);
}

function updateCountdown() {
  const now = new Date();
  const ref = new Date(MARKET_CONFIG.referenceMarketDay);
  const cycleMs = MARKET_CONFIG.cycleDays * 24 * 60 * 60 * 1000;
  
  // Calculate elapsed time in ms
  let elapsed = now.getTime() - ref.getTime();
  if (elapsed < 0) elapsed = 0;
  
  // Time within the current cycle (0 to cycleMs)
  const currentCycleOffset = elapsed % cycleMs;
  
  // Market day is active for the first 24 hours of the cycle
  const marketDayDuration = 24 * 60 * 60 * 1000;
  const isMarketDay = currentCycleOffset < marketDayDuration;
  
  let targetTime;
  let statusText = "";
  let dateString = "";
  
  if (isMarketDay) {
    statusText = TRANSLATIONS[currentLang].activeStatus;
    // Count down until the end of the market day (24 hours from start)
    targetTime = now.getTime() + (marketDayDuration - currentCycleOffset);
    
    const todayDate = new Date(now.getTime() - currentCycleOffset);
    dateString = (currentLang === 'ee' ? 'Egbe ƒe Asigbe: ' : 'Active Today: ') + todayDate.toLocaleDateString(currentLang === 'ee' ? 'ee-GH' : 'en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    
    // Add active pulsing indicators to visual elements
    document.querySelector(".countdown-card").style.borderColor = "var(--primary)";
    document.querySelector(".countdown-card").style.boxShadow = "0 8px 32px var(--primary-glow)";
  } else {
    statusText = TRANSLATIONS[currentLang].standbyStatus;
    // Time remaining until the next cycle starts
    const msToNextMarket = cycleMs - currentCycleOffset;
    targetTime = now.getTime() + msToNextMarket;
    
    const nextDate = new Date(now.getTime() + msToNextMarket);
    dateString = (currentLang === 'ee' ? 'Asigbe si gbɔna: ' : 'Next Market Day: ') + nextDate.toLocaleDateString(currentLang === 'ee' ? 'ee-GH' : 'en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    
    document.querySelector(".countdown-card").style.borderColor = "var(--border-glass)";
    document.querySelector(".countdown-card").style.boxShadow = "var(--shadow-main)";
  }
  
  document.getElementById('next-market-date').textContent = dateString;
  
  const badgeClass = isMarketDay ? 'badge text-success' : 'badge';
  const badgeText = isMarketDay ? TRANSLATIONS[currentLang].activeToday : TRANSLATIONS[currentLang].standby;
  document.getElementById('market-status-text').innerHTML = `
    <span class="${badgeClass}" style="margin-bottom: 0px; display: inline; padding: 2px 8px;">
      ${badgeText}
    </span> 
    <span style="margin-left: 8px; font-size: 13px;">${statusText}</span>
  `;
  
  const timeLeft = targetTime - now.getTime();
  
  const days = Math.floor(timeLeft / (24 * 60 * 60 * 1000));
  const hours = Math.floor((timeLeft % (24 * 60 * 60 * 1000)) / (60 * 60 * 1000));
  const minutes = Math.floor((timeLeft % (60 * 60 * 1000)) / (60 * 1000));
  const seconds = Math.floor((timeLeft % (60 * 1000)) / 1000);
  
  document.getElementById('days').textContent = String(days).padStart(2, '0');
  document.getElementById('hours').textContent = String(hours).padStart(2, '0');
  document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');
  document.getElementById('seconds').textContent = String(seconds).padStart(2, '0');
}

// --- 2. Price Index Component ---
function renderPrices(categoryFilter) {
  const container = document.getElementById("price-grid-container");
  container.innerHTML = "";
  
  const filtered = categoryFilter === "all" 
    ? COMMODITIES 
    : COMMODITIES.filter(c => c.category === categoryFilter);
    
  if (filtered.length === 0) {
    container.innerHTML = `<p class="section-desc" style="grid-column: 1/-1; text-align: center;">No commodities found in this category.</p>`;
    return;
  }
  
  filtered.forEach(item => {
    const changeClass = item.change > 0 ? "positive" : item.change < 0 ? "negative" : "neutral";
    const changeIcon = item.change > 0 ? "fa-arrow-trend-up" : item.change < 0 ? "fa-arrow-trend-down" : "fa-minus";
    const changeText = item.change > 0 ? `+${item.change}%` : `${item.change}%`;
    const sparklineColor = item.change > 0 ? "var(--primary)" : item.change < 0 ? "var(--danger)" : "var(--text-muted)";
    const sparklineD = generateSparklineD(item.trend);
    
    // Localized category label
    const catTrans = {
      "Tubers": TRANSLATIONS[currentLang].tubers,
      "Grains": TRANSLATIONS[currentLang].grains,
      "Vegetables": TRANSLATIONS[currentLang].veg,
      "Fruits & Vegetables": TRANSLATIONS[currentLang].veg,
      "Livestock": TRANSLATIONS[currentLang].livestock
    };
    const categoryLabel = catTrans[item.category] || item.category;
    const latestPriceLabel = TRANSLATIONS[currentLang].latestPrice;
    
    const card = document.createElement("div");
    card.className = "price-card glass";
    card.setAttribute("data-item-id", item.id);
    card.innerHTML = `
      <div class="card-header">
        <span class="item-cat">${categoryLabel}</span>
        <span class="change-badge ${changeClass}">
          <i class="fa-solid ${changeIcon}"></i> ${changeText}
        </span>
      </div>
      <h3 class="item-name">${item.name}</h3>
      <p class="item-unit">${TRANSLATIONS[currentLang].unit}: ${item.unit}</p>
      <div class="card-footer">
        <div class="price-display">
          <span class="label">${latestPriceLabel}</span>
          <span class="value">GH₵ ${item.price.toFixed(2)}</span>
        </div>
        <svg class="mini-sparkline" viewBox="0 0 80 30">
          <path d="${sparklineD}" fill="none" stroke="${sparklineColor}" stroke-width="2" />
        </svg>
      </div>
    `;
    
    card.addEventListener("click", () => {
      document.querySelectorAll(".price-card").forEach(c => c.classList.remove("active-card"));
      card.classList.add("active-card");
      showPriceDetail(item);
    });
    
    container.appendChild(card);
  });
}

// Generate path for micro sparkline
function generateSparklineD(trend) {
  if (!trend || trend.length < 2) return "";
  const min = Math.min(...trend);
  const max = Math.max(...trend);
  const range = max - min || 1;
  const width = 80;
  const height = 30;
  const padding = 3;
  
  return trend.map((val, i) => {
    const x = (i / (trend.length - 1)) * width;
    const y = height - padding - ((val - min) / range) * (height - 2 * padding);
    return `${i === 0 ? 'M' : 'L'} ${x.toFixed(1)} ${y.toFixed(1)}`;
  }).join(' ');
}

// Show price detailed chart modal
function showPriceDetail(item) {
  const detailCard = document.getElementById("price-detail-card");
  detailCard.classList.remove("hide");
  
  // Localized category
  const catTrans = {
    "Tubers": TRANSLATIONS[currentLang].tubers,
    "Grains": TRANSLATIONS[currentLang].grains,
    "Vegetables": TRANSLATIONS[currentLang].veg,
    "Fruits & Vegetables": TRANSLATIONS[currentLang].veg,
    "Livestock": TRANSLATIONS[currentLang].livestock
  };
  
  document.getElementById("detail-category").textContent = catTrans[item.category] || item.category;
  document.getElementById("detail-name").textContent = item.name;
  document.getElementById("detail-description").textContent = item.description;
  document.getElementById("detail-price").textContent = `GH₵ ${item.price.toFixed(2)}`;
  document.getElementById("detail-unit").textContent = item.unit;
  
  const changeVal = document.getElementById("detail-change");
  changeVal.textContent = item.change > 0 ? `+${item.change}%` : `${item.change}%`;
  changeVal.className = "metric-val " + (item.change > 0 ? "text-success" : item.change < 0 ? "text-danger" : "text-muted");
  
  // Render SVG Chart
  drawLargeChart(item.trend, item.change >= 0);
  
  // Set up Purchase Calculator for this item
  const calcQty = document.getElementById("calc-qty");
  const calcUnit = document.getElementById("calc-unit");
  const calcTotal = document.getElementById("calc-total");
  
  calcQty.value = 1;
  calcUnit.textContent = item.unit;
  calcTotal.textContent = `GH₵ ${item.price.toFixed(2)}`;
  
  // Remove existing listeners to avoid stacking
  const newCalcQty = calcQty.cloneNode(true);
  calcQty.parentNode.replaceChild(newCalcQty, calcQty);
  
  newCalcQty.addEventListener("input", () => {
    let qty = parseInt(newCalcQty.value);
    if (isNaN(qty) || qty < 1) qty = 1;
    calcTotal.textContent = `GH₵ ${(qty * item.price).toFixed(2)}`;
  });
  
  // Scroll to details card smoothly
  detailCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function drawLargeChart(trend, isPositive) {
  const min = Math.min(...trend);
  const max = Math.max(...trend);
  const range = max - min || 1;
  const width = 300;
  const height = 120;
  const padding = 10;
  
  const points = trend.map((val, i) => {
    const x = (i / (trend.length - 1)) * width;
    const y = height - padding - ((val - min) / range) * (height - 2 * padding);
    return { x, y };
  });
  
  const strokePath = points.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.x.toFixed(1)} ${p.y.toFixed(1)}`).join(' ');
  const fillPath = `${strokePath} L ${width} ${height} L 0 ${height} Z`;
  
  const chartPath = document.getElementById('chart-path');
  const chartFill = document.getElementById('chart-fill');
  
  chartPath.setAttribute('d', strokePath);
  chartFill.setAttribute('d', fillPath);
  
  const strokeColor = isPositive ? 'var(--primary)' : 'var(--danger)';
  chartPath.setAttribute('stroke', strokeColor);
  
  const stop1 = document.querySelector('#chart-grad stop:first-child');
  stop1.setAttribute('stop-color', strokeColor);
}

// --- 3. Interactive Map logic ---
const ZONE_DETAILS = {
  agriculture: {
    name: "Agriculture Zone (Zone A)",
    desc: "The primary commercial space in Akatsi. Packed with fresh tubers (yam, cassava), bag grains (maize, beans), and organic local vegetables shipped directly from neighboring irrigation fields.",
    produce: "Yams, Cassava, Shallots, Maize, Plantain",
    access: "North Access Gate / Main Bypass",
    stalls: "180+ Stalls / Wholesale Depots"
  },
  clothing: {
    name: "Apparel & Textiles (Zone B)",
    desc: "A colorful, vibrant quadrant showing off the cultural heritage of the Volta region. Offers high-quality handwoven Ewe Kente, imported wax prints, boutique designs, and tailors.",
    produce: "Kente Weaves, Fabrics, Sandals, Ready-Wear",
    access: "Eastern Main Gate",
    stalls: "120+ Specialized Stalls"
  },
  livestock: {
    name: "Livestock Yard (Zone C)",
    desc: "Located on the periphery to assure cleanliness and sanitary compliance. Traders bring high-grade goats, sheep, guinea fowls, and local chickens from the surrounding Savannah districts.",
    produce: "Local Goats, Sheep, Turkeys, Free-range Fowls",
    access: "Western Lorry Terminal",
    stalls: "85 Livestock Pens"
  },
  food: {
    name: "Food Court & Transport (Zone D)",
    desc: "The busy heart of the market. Filled with local chop bars serving hot Akple with okra soup or Banku with grilled tilapia. Connects directly to the trotro station for easy transit.",
    produce: "Akple Joints, Drinks, Transport ticketing",
    access: "Central Lorry Park & Station",
    stalls: "60+ Food Stalls & Lorry Terminals"
  }
};

let activeSelectedZone = null;

function initMapInteractivity() {
  const zones = document.querySelectorAll(".map-zone");
  const infoPanel = document.getElementById("map-info-panel");
  const highlights = document.getElementById("zone-highlights");
  
  const zoneNameEl = document.getElementById("selected-zone-name");
  const zoneDescEl = document.getElementById("selected-zone-desc");
  const hlProduceEl = document.getElementById("hl-produce");
  const hlAccessEl = document.getElementById("hl-access");
  const hlCountEl = document.getElementById("hl-count");
  
  const filterBtn = document.getElementById("filter-zone-btn");

  zones.forEach(zone => {
    const zoneId = zone.getAttribute("data-zone");
    
    zone.addEventListener("mouseenter", () => {
      if (activeSelectedZone) return; // Do not overwrite on hover if a zone is selected
      showZoneInfo(zoneId);
    });

    zone.addEventListener("mouseleave", () => {
      if (activeSelectedZone) return;
      resetZoneInfo();
    });

    zone.addEventListener("click", () => {
      // Toggle active zone selection
      zones.forEach(z => z.classList.remove("active-zone"));
      
      if (activeSelectedZone === zoneId) {
        // Deselect
        activeSelectedZone = null;
        resetZoneInfo();
      } else {
        // Select
        activeSelectedZone = zoneId;
        zone.classList.add("active-zone");
        showZoneInfo(zoneId);
      }
    });
  });

  filterBtn.addEventListener("click", () => {
    if (!activeSelectedZone) return;
    
    // Set the dropdown filters in directory
    document.getElementById("zone-select").value = activeSelectedZone;
    filterDirectory();
    
    // Scroll to directory
    document.getElementById("directory-sec").scrollIntoView({ behavior: 'smooth' });
  });

  function showZoneInfo(zoneId) {
    const data = ZONE_DETAILS[zoneId];
    if (!data) return;
    
    zoneNameEl.textContent = data.name;
    zoneDescEl.textContent = data.desc;
    hlProduceEl.textContent = data.produce;
    hlAccessEl.textContent = data.access;
    hlCountEl.textContent = data.stalls;
    
    highlights.classList.remove("hide");
  }

  function resetZoneInfo() {
    zoneNameEl.textContent = "Select a Quadrant";
    zoneDescEl.textContent = "Hover over any region on the map to inspect its general merchandise, accessibility, and active stalls.";
    highlights.classList.add("hide");
  }
}

// --- 4. Stall & Vendor Directory ---
function renderVendorDirectory(data = VENDORS) {
  const container = document.getElementById("directory-grid-container");
  container.innerHTML = "";
  
  if (data.length === 0) {
    const noVendorsMsg = currentLang === 'ee' 
      ? "Womekpɔ asitsala aɖeke si sɔ kple wò didi o."
      : "No vendors found matching your current filter settings.";
    container.innerHTML = `
      <div style="grid-column: 1/-1; text-align: center; padding: 40px;" class="glass">
        <i class="fa-regular fa-face-frown" style="font-size: 32px; color: var(--text-muted); margin-bottom: 12px;"></i>
        <p style="color: var(--text-secondary);">${noVendorsMsg}</p>
      </div>
    `;
    return;
  }
  
  data.forEach(vendor => {
    const card = document.createElement("div");
    card.className = "vendor-card glass";
    
    const verifiedText = TRANSLATIONS[currentLang].dirVerified;
    const verifiedBadge = vendor.verified 
      ? `<span class="v-badge verified"><i class="fa-solid fa-circle-check"></i> ${verifiedText}</span>`
      : "";
      
    // Generate stars
    let starsHtml = "";
    const wholeStars = Math.floor(vendor.rating);
    for (let i = 0; i < 5; i++) {
      if (i < wholeStars) {
        starsHtml += `<i class="fa-solid fa-star"></i>`;
      } else {
        starsHtml += `<i class="fa-regular fa-star"></i>`;
      }
    }
    
    // Generate product tags
    const tagsHtml = vendor.products.map(p => `<span class="prod-tag">${p}</span>`).join("");
    
    const contactCallLabel = TRANSLATIONS[currentLang].dirContactCall;
    const inquireText = TRANSLATIONS[currentLang].dirInquireBtn;
    
    card.innerHTML = `
      <div class="vendor-header">
        <div class="vendor-title">
          <h3>${vendor.name}</h3>
          <span class="vendor-stall"><i class="fa-solid fa-location-dot"></i> ${vendor.stall}</span>
        </div>
        <div class="vendor-badges">
          ${verifiedBadge}
          <span class="v-badge zone">${vendor.zone.toUpperCase()}</span>
        </div>
      </div>
      <div class="rating-stars">
        ${starsHtml}
        <span>${vendor.rating.toFixed(1)}</span>
      </div>
      <p class="vendor-bio">"${vendor.bio}"</p>
      <div class="vendor-products">
        <h4>${currentLang === 'ee' ? 'Nudzradzra Siwo Li' : 'Available Commodities'}</h4>
        <div class="prod-tags">${tagsHtml}</div>
      </div>
      <div class="vendor-footer">
        <div class="vendor-contact">
          <span class="lbl">${contactCallLabel}</span>
          <span class="num">${vendor.phone}</span>
        </div>
        <div style="display: flex; gap: 8px;">
          <button class="inquire-btn" data-vendor-id="${vendor.id}" title="${inquireText}">
            <i class="fa-solid fa-envelope"></i>
          </button>
          <a href="tel:${vendor.phone.replace(/\s+/g, '')}" class="call-btn" title="Call Vendor">
            <i class="fa-solid fa-phone"></i>
          </a>
        </div>
      </div>
    `;
    
    container.appendChild(card);
  });
}

function filterDirectory() {
  const query = document.getElementById("vendor-search-input").value.toLowerCase();
  const category = document.getElementById("category-select").value;
  const zone = document.getElementById("zone-select").value;
  
  const filtered = VENDORS.filter(v => {
    // 1. Matches Search Text
    const matchSearch = v.name.toLowerCase().includes(query) || 
                        v.products.some(p => p.toLowerCase().includes(query)) ||
                        v.bio.toLowerCase().includes(query) ||
                        v.stall.toLowerCase().includes(query);
                        
    // 2. Matches Category
    const matchCategory = category === "all" || v.category === category;
    
    // 3. Matches Zone
    const matchZone = zone === "all" || v.zone === zone;
    
    return matchSearch && matchCategory && matchZone;
  });
  
  renderVendorDirectory(filtered);
  
  // Update Results Counter
  const total = VENDORS.length;
  const count = filtered.length;
  const counterEl = document.getElementById("directory-results-count");
  if (counterEl) {
    if (currentLang === 'ee') {
      counterEl.textContent = `Wole asitsala ${count} fiam tso ${total} me`;
    } else {
      counterEl.textContent = `Showing ${count} of ${total} registered stalls`;
    }
  }
}

// --- 5. Transport Guide & News Boards ---
function renderTransportGuide() {
  const tbody = document.getElementById("transport-table-body");
  tbody.innerHTML = "";
  
  TRANSPORT_ROUTES.forEach(route => {
    let freq = route.frequency;
    let type = route.type;
    
    // Localize simple terms in transit
    if (currentLang === 'ee') {
      if (freq.includes("Every 30 mins")) {
        freq = "Miniti 30 ɖesiaɖe (Asigbe), Gaƒoƒo ɖeka (Ŋkeke bubu)";
      } else if (freq.includes("Regular departures")) {
        freq = "Trom dede madzudzɔe";
      }
      
      if (type.includes("Trotro / Sprinter")) {
        type = "Trom / Sprinter";
      } else if (type.includes("Shared Taxi")) {
        type = "Taksi Si Woɖo";
      }
    }
    
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td><strong>${route.from}</strong> to ${route.to}</td>
      <td>${type}</td>
      <td>${freq}</td>
      <td><span class="text-accent" style="font-weight: 700;">${route.fare}</span></td>
    `;
    tbody.appendChild(tr);
  });
}

function renderNewsAndUpdates() {
  const container = document.getElementById("news-list-container");
  container.innerHTML = "";
  
  NEWS.forEach(item => {
    const div = document.createElement("div");
    div.className = "news-item";
    div.innerHTML = `
      <div class="news-meta">
        <span><i class="fa-regular fa-clock"></i> ${item.date}</span>
        <span>${item.readTime}</span>
      </div>
      <h4>${item.title}</h4>
      <p>${item.summary}</p>
    `;
    container.appendChild(div);
  });
}

// --- 6. Navigation Link Highlighting on Scroll ---
function initNavigation() {
  const sections = document.querySelectorAll("section");
  const navLinks = document.querySelectorAll("nav ul li a");
  
  window.addEventListener("scroll", () => {
    let current = "";
    
    sections.forEach(section => {
      const sectionTop = section.offsetTop;
      const sectionHeight = section.clientHeight;
      if (window.scrollY >= (sectionTop - 120)) {
        current = section.getAttribute("id");
      }
    });
    
    navLinks.forEach(link => {
      link.classList.remove("active");
      if (link.getAttribute("href") === `#${current}`) {
        link.classList.add("active");
      }
    });
  });
}

// --- 7. Language & Translations Controller ---
let currentLang = "en";

function initLanguage() {
  const switcherBtns = document.querySelectorAll(".lang-switcher .lang-btn");
  switcherBtns.forEach(btn => {
    btn.addEventListener("click", (e) => {
      const selectedLang = e.target.getAttribute("data-lang");
      if (selectedLang === currentLang) return;
      
      currentLang = selectedLang;
      
      // Update active switcher class
      switcherBtns.forEach(b => b.classList.remove("active"));
      e.target.classList.add("active");
      
      translatePage(currentLang);
      
      // Re-render items that depend on dynamic translation
      updateCountdown();
      const activeTab = document.querySelector(".price-filters .filter-tab.active");
      const activeCat = activeTab ? activeTab.getAttribute("data-price-cat") : "all";
      renderPrices(activeCat);
      filterDirectory();
      renderTransportGuide();
      renderNewsAndUpdates();
      
      // Update map info panel details if zone is selected
      if (activeSelectedZone) {
        showZoneInfo(activeSelectedZone);
      }
    });
  });
}

function translatePage(lang) {
  const dict = TRANSLATIONS[lang];
  if (!dict) return;
  
  // Translate elements with data-i18n
  document.querySelectorAll("[data-i18n]").forEach(el => {
    const key = el.getAttribute("data-i18n");
    if (dict[key]) {
      // Preserve icon elements inside section badges/links if any
      const icon = el.querySelector("i");
      if (icon) {
        el.innerHTML = "";
        el.appendChild(icon);
        el.appendChild(document.createTextNode(" " + dict[key]));
      } else {
        el.textContent = dict[key];
      }
    }
  });
  
  // Translate inputs with data-i18n-placeholder
  document.querySelectorAll("[data-i18n-placeholder]").forEach(el => {
    const key = el.getAttribute("data-i18n-placeholder");
    if (dict[key]) {
      el.setAttribute("placeholder", dict[key]);
    }
  });
  
  // Translate table headers
  const thRoute = document.querySelector("table th:nth-child(1)");
  if (thRoute) thRoute.textContent = dict.thRoute;
  const thType = document.querySelector("table th:nth-child(2)");
  if (thType) thType.textContent = dict.thType;
  const thFreq = document.querySelector("table th:nth-child(3)");
  if (thFreq) thFreq.textContent = dict.thFreq;
  const thFare = document.querySelector("table th:nth-child(4)");
  if (thFare) thFare.textContent = dict.thFare;
  
  // Translate category dropdown options dynamically
  const catOptions = {
    "all": dict.allCategories,
    "Tubers": dict.tubers,
    "Vegetables": dict.veg,
    "Livestock": dict.livestock,
    "Clothing": dict.clothing,
    "Prepared Food": dict.food
  };
  
  document.querySelectorAll("#category-select option").forEach(opt => {
    const val = opt.value;
    if (catOptions[val]) opt.textContent = catOptions[val];
  });
  
  // Translate zone select options
  const zoneOptions = {
    "all": dict.allCategories,
    "agriculture": dict.zoneAgri || "Agriculture Zone",
    "clothing": dict.zoneCloth || "Apparel & Textiles",
    "livestock": dict.zoneLive || "Livestock Yard",
    "food": dict.zoneFood || "Food & Transport"
  };
  
  document.querySelectorAll("#zone-select option").forEach(opt => {
    const val = opt.value;
    if (zoneOptions[val]) opt.textContent = zoneOptions[val];
  });
}

// --- 8. Vendor Inquiry Modal Logic ---
let currentSelectedVendor = null;

function initInquiryModal() {
  const modal = document.getElementById("inquiry-modal");
  const closeBtn = document.getElementById("close-inquiry-modal");
  const submitBtn = document.getElementById("submit-inquiry");
  const messageInput = document.getElementById("inquiry-message");
  
  // Delegate event listener for inquire button (since cards are re-rendered dynamically)
  document.getElementById("directory-grid-container").addEventListener("click", (e) => {
    const btn = e.target.closest(".inquire-btn");
    if (btn) {
      const vendorId = parseInt(btn.getAttribute("data-vendor-id"));
      const vendor = VENDORS.find(v => v.id === vendorId);
      if (vendor) {
        currentSelectedVendor = vendor;
        document.getElementById("modal-title").textContent = (currentLang === 'ee' ? 'Gblɔ Nya Na ' : 'Contact ') + vendor.name;
        document.getElementById("modal-vendor-stall").textContent = vendor.stall;
        messageInput.value = "";
        modal.classList.add("show");
      }
    }
  });
  
  closeBtn.addEventListener("click", () => {
    modal.classList.remove("show");
  });
  
  // Close on clicking backdrop
  modal.addEventListener("click", (e) => {
    if (e.target === modal) {
      modal.classList.remove("show");
    }
  });
  
  submitBtn.addEventListener("click", () => {
    const message = messageInput.value.trim();
    if (!message) return;
    
    modal.classList.remove("show");
    
    // Show toast
    const successMsg = currentLang === 'ee' 
      ? `Wò nya la yi na ${currentSelectedVendor.name} successfully!`
      : `Message sent to ${currentSelectedVendor.name} successfully!`;
    showToast(successMsg);
  });
}

function showToast(message) {
  const container = document.getElementById("toast-container");
  const toast = document.createElement("div");
  toast.className = "toast";
  toast.innerHTML = `
    <i class="fa-solid fa-circle-check text-primary"></i>
    <span>${message}</span>
  `;
  container.appendChild(toast);
  
  // Remove toast after 3.5 seconds
  setTimeout(() => {
    toast.classList.add("hide");
    setTimeout(() => {
      toast.remove();
    }, 300);
  }, 3500);
}
