/* =========================================
   T.S. Pattani - Member/Frontend JavaScript (v1.7)
   ========================================= */

// ฟังก์ชันตรวจสอบความถูกต้องของฟอร์มสมัครสมาชิก (register.php)
function validatePassword() {
    var password = document.getElementById("password").value;
    var confirmPassword = document.getElementById("confirm_password").value;
    var phone = document.getElementById("member_phone").value;

    // เช็คว่าเบอร์โทรเป็นตัวเลข 10 หลักหรือไม่
    if(!/^[0-9]{10}$/.test(phone)) {
        alert("กรุณากรอกเบอร์โทรศัพท์ให้ครบ 10 หลัก (เฉพาะตัวเลขเท่านั้น)");
        return false;
    }

    // เช็ครหัสผ่านให้ตรงกัน
    if (password !== confirmPassword) {
        alert("รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง!");
        return false;
    }
    return true;
}

// ฟังก์ชันซ่อน Alert อัตโนมัติ (ใช้ร่วมกันทุกหน้าฝั่งลูกค้า)
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        let alerts = document.querySelectorAll('.alert-success, .alert-error');
        alerts.forEach(alert => {
            alert.style.transition = "opacity 0.5s";
            alert.style.opacity = "0";
            setTimeout(() => alert.style.display = 'none', 500);
        });
    }, 3000);
});

/* =========================================
   Navbar Controls
   ========================================= */
// เปิด-ปิด Dropdown โปรไฟล์
function toggleDropdown() {
    document.getElementById("userDropdown").classList.toggle("show");
}

// เปิด-ปิด เมนูสำหรับมือถือ (แฮมเบอร์เกอร์)
function toggleMobileMenu() {
    document.getElementById("navMenu").classList.toggle("show");
}

// คลิกที่อื่นบนจอเพื่อปิด Dropdown อัตโนมัติ
window.onclick = function(event) {
    if (!event.target.closest('.user-dropdown')) {
        var dropdowns = document.getElementsByClassName("dropdown-content");
        for (var i = 0; i < dropdowns.length; i++) {
            var openDropdown = dropdowns[i];
            if (openDropdown.classList.contains('show')) {
                openDropdown.classList.remove('show');
            }
        }
    }
}

/* ==========================================================================
   T.S. Pattani - 5-Step Booking Wizard Controller (HCI & Progressive Disclosure)
   Zero Inline Style Standard - Complete State & Interaction Management
   ========================================================================== */

const wizardState = {
    currentStep: 1,
    selectedDate: new Date().toISOString().split('T')[0],
    currentCalMonth: new Date().getMonth(),
    currentCalYear: new Date().getFullYear(),
    dayRateCategory: 'normal',
    dayRatePrice: 180,
    dayRateLabel: 'วันจันทร์ (180 ฿/ชม.)',
    selectedStartTime: '',
    bookingNickname: '',
    matrixData: null,
    // Map of slotStart => { courtId, courtName, price, slotStart, slotEnd }
    selectedSlots: new Map(),
    selectedCourtId: null,
    selectedCourtName: '',
    selectedHours: 0,
    courtPriceTotal: 0,
    rentalPriceTotal: 0,
    productPriceTotal: 0,
    grandTotal: 0
};

// Thai Month Names for Calendar
const thaiMonthNames = [
    "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน",
    "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"
];
const thaiDayNames = ["อา.", "จ.", "อ.", "พ.", "พฤ.", "ศ.", "ส."];

// Initialize Booking Wizard
function initBookingWizard() {
    let nicknameInput = document.getElementById('step3_nickname');
    if (nicknameInput) {
        wizardState.bookingNickname = nicknameInput.value.trim();
    }
    let todayStr = new Date().toISOString().split('T')[0];
    wizardState.selectedDate = todayStr;
    wizardState.currentCalMonth = new Date().getMonth();
    wizardState.currentCalYear = new Date().getFullYear();

    let inputDate = document.getElementById('input_booking_date');
    if (inputDate) inputDate.value = todayStr;

    // Render Calendar and load court data
    renderCalendar();
    loadCourtMatrixForWizard(todayStr);
    updateWizardStepperUI(1);
}

// Navigate between Wizard Steps
function goToStep(targetStep) {
    if (targetStep < 1 || targetStep > 5) return;

    // Validation before stepping forward
    if (targetStep > wizardState.currentStep) {
        if (targetStep === 2) {
            if (!wizardState.selectedDate) {
                alert("กรุณาเลือกวันที่ต้องการใช้บริการ");
                return;
            }
            renderTimeSlots();
        } else if (targetStep === 3) {
            if (!wizardState.selectedStartTime) {
                alert("กรุณาเลือกเวลาเริ่มต้น");
                return;
            }
            renderCourtHourBlocks();
        } else if (targetStep === 4) {
            if (wizardState.selectedSlots.size === 0) {
                alert("กรุณาเลือกสนามอย่างน้อย 1 ช่วงเวลา");
                return;
            }
            updateStep4Summary();
        } else if (targetStep === 5) {
            updateInvoiceReview();
        }
    } else {
        // If stepping backwards
        if (targetStep === 2) {
            renderTimeSlots();
        } else if (targetStep === 3) {
            renderCourtHourBlocks();
        } else if (targetStep === 4) {
            updateStep4Summary();
        }
    }

    wizardState.currentStep = targetStep;

    // Hide all panels & show target panel
    for (let s = 1; s <= 5; s++) {
        let panel = document.getElementById('stepPanel' + s);
        if (panel) {
            if (s === targetStep) {
                panel.classList.add('active');
            } else {
                panel.classList.remove('active');
            }
        }
    }

    updateWizardStepperUI(targetStep);

    // Scroll to top of wizard
    let wrapper = document.querySelector('.wizard-page-wrapper');
    if (wrapper) {
        wrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Allow clicking on previous completed steps to jump back
function jumpToWizardStep(targetStep) {
    if (targetStep < wizardState.currentStep) {
        goToStep(targetStep);
    }
}

// Update Stepper Progress UI
function updateWizardStepperUI(currentStep) {
    for (let s = 1; s <= 5; s++) {
        let node = document.getElementById('stepperNode' + s);
        if (!node) continue;

        node.classList.remove('active', 'completed', 'upcoming');
        if (s < currentStep) {
            node.classList.add('completed');
        } else if (s === currentStep) {
            node.classList.add('active');
        } else {
            node.classList.add('upcoming');
        }
    }

    let fillBar = document.getElementById('stepperFillBar');
    if (fillBar) {
        let percent = ((currentStep - 1) / 4) * 100;
        fillBar.style.width = percent + '%';
    }
}

/* ==========================================================================
   Step 1: Visual Interactive Calendar
   ========================================================================== */
function renderCalendar() {
    let titleEl = document.getElementById('calMonthTitle');
    let gridEl = document.getElementById('calendarGrid');
    if (!titleEl || !gridEl) return;

    let year = wizardState.currentCalYear;
    let month = wizardState.currentCalMonth;
    titleEl.innerText = `${thaiMonthNames[month]} ${year + 543} (${year})`;

    let html = '';
    // Day headers
    thaiDayNames.forEach((dName, idx) => {
        let weekendClass = (idx === 0 || idx === 6) ? ' weekend' : '';
        html += `<div class="calendar-day-header${weekendClass}">${dName}</div>`;
    });

    // First day of month & number of days
    let firstDayIndex = new Date(year, month, 1).getDay();
    let daysInMonth = new Date(year, month + 1, 0).getDate();

    // Empty cells before first day
    for (let e = 0; e < firstDayIndex; e++) {
        html += `<div class="calendar-date-cell empty"></div>`;
    }

    let today = new Date();
    today.setHours(0, 0, 0, 0);

    for (let day = 1; day <= daysInMonth; day++) {
        let cellDate = new Date(year, month, day);
        cellDate.setHours(0, 0, 0, 0);

        let dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        let isPast = cellDate < today;
        let isToday = cellDate.getTime() === today.getTime();
        let isSelected = dateStr === wizardState.selectedDate;

        let classes = ['calendar-date-cell'];
        if (isPast) classes.push('disabled');
        if (isToday) classes.push('today');
        if (isSelected) classes.push('selected');

        let clickAttr = isPast ? '' : `onclick="onSelectCalendarDate('${dateStr}')"`;
        html += `<div class="${classes.join(' ')}" ${clickAttr}>${day}</div>`;
    }

    gridEl.innerHTML = html;
}

function navigateCalendarMonth(dir) {
    wizardState.currentCalMonth += dir;
    if (wizardState.currentCalMonth < 0) {
        wizardState.currentCalMonth = 11;
        wizardState.currentCalYear--;
    } else if (wizardState.currentCalMonth > 11) {
        wizardState.currentCalMonth = 0;
        wizardState.currentCalYear++;
    }
    renderCalendar();
}

function selectQuickDate(type) {
    let target = new Date();
    if (type === 'tomorrow') {
        target.setDate(target.getDate() + 1);
    }
    let dateStr = target.toISOString().split('T')[0];

    document.querySelectorAll('.btn-quick-date').forEach(b => b.classList.remove('active'));
    let btn = (type === 'tomorrow') ? document.getElementById('btnQuickTomorrow') : document.getElementById('btnQuickToday');
    if (btn) btn.classList.add('active');

    wizardState.currentCalMonth = target.getMonth();
    wizardState.currentCalYear = target.getFullYear();

    onSelectCalendarDate(dateStr);
}

function onSelectCalendarDate(dateStr) {
    wizardState.selectedDate = dateStr;
    let inputDate = document.getElementById('input_booking_date');
    if (inputDate) inputDate.value = dateStr;

    // Reset slot selection if date changes
    wizardState.selectedSlots.clear();
    wizardState.selectedCourtId = null;
    wizardState.selectedCourtName = '';
    wizardState.selectedHours = 0;
    wizardState.courtPriceTotal = 0;

    renderCalendar();
    loadCourtMatrixForWizard(dateStr);
}

function loadCourtMatrixForWizard(dateStr) {
    let banner = document.getElementById('datePriceBanner');
    let textSpan = document.getElementById('datePriceText');
    if (textSpan) textSpan.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังตรวจสอบอัตราค่าบริการ...';

    fetch('actions/get_court_matrix.php?date=' + encodeURIComponent(dateStr))
    .then(res => res.json())
    .then(res => {
        if (res.status === 'success') {
            wizardState.matrixData = res;
            wizardState.dayRateCategory = res.rate_category;
            wizardState.dayRateLabel = res.rate_label;

            let dow = res.day_of_week;
            let ratePrice = (dow === 0 || dow === 6) ? 200 : (dow === 2 || dow === 4 ? 150 : 180);
            wizardState.dayRatePrice = ratePrice;

            if (banner && textSpan) {
                banner.className = 'date-price-banner ' + res.rate_category;
                textSpan.innerHTML = `อัตราค่าบริการสำหรับ <strong>${res.day_name} (${formatDateThai(dateStr)})</strong>: <span class="price-tag-value">${res.rate_label}</span>`;
            }
        }
    })
    .catch(err => {
        console.error('Court matrix fetch error:', err);
        if (textSpan) textSpan.innerText = 'ไม่สามารถดึงข้อมูลอัตราค่าบริการได้';
    });
}

function formatDateThai(dateStr) {
    if (!dateStr) return '-';
    let parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    return `${parts[2]}/${parts[1]}/${parseInt(parts[0]) + 543}`;
}

/* ==========================================================================
   Step 2: Operating Hours Grid (Start Time Selection)
   ========================================================================== */
function renderTimeSlots() {
    let container = document.getElementById('timeSlotGrid');
    if (!container) return;

    if (!wizardState.matrixData || !wizardState.matrixData.slots) {
        container.innerHTML = '<p class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> กำลังโหลดช่วงเวลา...</p>';
        return;
    }

    let slots = wizardState.matrixData.slots;
    let courts = wizardState.matrixData.courts;

    let now = new Date();
    let isToday = (wizardState.selectedDate === now.toISOString().split('T')[0]);
    let currentHour = now.getHours();

    let html = '';
    slots.forEach(slot => {
        let slotHour = parseInt(slot.start.split(':')[0]);
        let isPastHour = isToday && (slotHour <= currentHour);

        // ตรวจสอบว่าช่วงเวลานี้เปิดให้บริการหรือไม่ (เช็คจากสนามทั้งหมด)
        let hasOpenCourt = false;
        if (courts) {
            courts.forEach(c => {
                let sInfo = c.slots ? c.slots[slot.start] : null;
                if (sInfo && sInfo.status !== 'closed') {
                    hasOpenCourt = true;
                }
            });
        }

        let isDisabled = isPastHour || !hasOpenCourt;
        let statusText = 'เปิดให้บริการ';
        let cardClass = 'time-slot-card';

        if (isPastHour) {
            statusText = 'เลยเวลาแล้ว';
            cardClass += ' disabled';
        } else if (!hasOpenCourt) {
            statusText = 'นอกเวลาทำการ';
            cardClass += ' disabled';
        }

        let clickAttr = isDisabled ? '' : `onclick="onSelectStartTime('${slot.start}')"`;

        html += `
            <div class="${cardClass}" ${clickAttr} title="${slot.start} - ${slot.end}">
                <i class="fas fa-clock"></i>
                <div class="time-slot-time">${slot.start} น.</div>
                <div class="time-slot-status">${statusText}</div>
            </div>
        `;
    });

    container.innerHTML = html;
}

function onSelectStartTime(startStr) {
    wizardState.selectedStartTime = startStr;
    let inputStart = document.getElementById('input_start_time');
    if (inputStart) inputStart.value = startStr;

    // Transition immediately to Step 3 as required by HCI Progressive Disclosure
    goToStep(3);
}

/* ==========================================================================
   Step 3: Court Selection & Consecutive Slot / Scroll Multi-Hour
   ========================================================================== */
function renderCourtHourBlocks() {
    let container = document.getElementById('courtHourBlocksContainer');
    if (!container || !wizardState.matrixData) return;

    let slots = wizardState.matrixData.slots;
    let courts = wizardState.matrixData.courts;
    let startHour = parseInt(wizardState.selectedStartTime.split(':')[0]);

    // กรองเฉพาะช่วงเวลาที่เริ่มตั้งแต่ selectedStartTime เป็นต้นไป
    let availableSlots = slots.filter(s => parseInt(s.start.split(':')[0]) >= startHour);

    let html = '';
    availableSlots.forEach((slot, index) => {
        let isFirstHour = (index === 0);
        let hourLabel = isFirstHour 
            ? `ชั่วโมงที่ 1 : ${slot.start} - ${slot.end} น.` 
            : `ชั่วโมงถัดไป (จองต่อเนื่อง) : ${slot.start} - ${slot.end} น.`;

        html += `
            <div class="slot-hour-block">
                <div class="slot-hour-header">
                    <div class="slot-hour-title">
                        <i class="fas ${isFirstHour ? 'fa-star text-warning' : 'fa-clock text-primary'}"></i>
                        ${hourLabel}
                    </div>
                    <span class="slot-rate-badge">
                        <i class="fas fa-tag"></i> ${wizardState.dayRateLabel}
                    </span>
                </div>
                <div class="court-cards-grid">
        `;

        courts.forEach(court => {
            let sInfo = court.slots ? court.slots[slot.start] : null;
            let status = sInfo ? sInfo.status : 'closed';
            let bookedBy = sInfo ? sInfo.booked_by : '';
            let price = sInfo ? sInfo.price : wizardState.dayRatePrice;

            // ตรวจสอบว่า slot นี้ถูกเลือกไว้ใน state หรือไม่
            let isCurrentSlotSelected = false;
            let selectedEntry = wizardState.selectedSlots.get(slot.start);
            if (selectedEntry && selectedEntry.courtId === court.court_id) {
                isCurrentSlotSelected = true;
            }

            let cardClass = 'court-card-v2 ';
            let badgeHtml = '';
            let clickAttr = '';

            if (isCurrentSlotSelected) {
                cardClass += 'status-selected';
                badgeHtml = '<span class="court-card-badge">✓ กำลังเลือก</span>';
                clickAttr = `onclick="toggleCourtSlot('${slot.start}', '${slot.end}', ${court.court_id}, '${court.court_name}', ${price})"`;
            } else if (status === 'available') {
                cardClass += 'status-available';
                badgeHtml = '<span class="court-card-badge">● ว่าง</span>';
                clickAttr = `onclick="toggleCourtSlot('${slot.start}', '${slot.end}', ${court.court_id}, '${court.court_name}', ${price})"`;
            } else if (status === 'pending') {
                cardClass += 'status-pending';
                badgeHtml = `<span class="court-card-badge" title="รอตรวจสอบ">● รอตรวจสอบ (${bookedBy || 'ผู้ใช้งาน'})</span>`;
            } else if (status === 'booked') {
                cardClass += 'status-booked';
                badgeHtml = `<span class="court-card-badge" title="จองแล้ว">● จองแล้ว (${bookedBy || 'ผู้ใช้งาน'})</span>`;
            } else if (status === 'maintenance') {
                cardClass += 'status-maintenance';
                badgeHtml = '<span class="court-card-badge">● ปิดปรับปรุง</span>';
            } else {
                cardClass += 'status-maintenance';
                badgeHtml = '<span class="court-card-badge">● ปิดบริการ</span>';
            }

            html += `
                <div class="${cardClass}" ${clickAttr}>
                    <i class="fas fa-map-marker-alt court-card-icon"></i>
                    <h4 class="court-card-title">${court.court_name}</h4>
                    ${badgeHtml}
                </div>
            `;
        });

        html += `
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    updateStickySummaryBar();
}

// Toggle slot selection (consecutive multi-hour support)
function toggleCourtSlot(slotStart, slotEnd, courtId, courtName, price) {
    if (wizardState.selectedSlots.has(slotStart)) {
        let cur = wizardState.selectedSlots.get(slotStart);
        if (cur.courtId === courtId) {
            // Unselect this slot
            wizardState.selectedSlots.delete(slotStart);
            if (wizardState.selectedSlots.size === 0) {
                wizardState.selectedCourtId = null;
                wizardState.selectedCourtName = '';
            }
            renderCourtHourBlocks();
            return;
        } else {
            // Change court for this slot
            wizardState.selectedSlots.set(slotStart, {
                courtId: courtId,
                courtName: courtName,
                price: price,
                slotStart: slotStart,
                slotEnd: slotEnd
            });
            wizardState.selectedCourtId = courtId;
            wizardState.selectedCourtName = courtName;
            renderCourtHourBlocks();
            return;
        }
    }

    // Check same court rule for consecutive hours
    if (wizardState.selectedCourtId && wizardState.selectedCourtId !== courtId) {
        if (confirm(`สำหรับการจองต่อเนื่องหลายชั่วโมง ต้องเป็นสนามเดียวกัน\nท่านต้องการเปลี่ยนการจองทั้งหมดเป็น "${courtName}" หรือไม่?`)) {
            wizardState.selectedSlots.forEach((slotData) => {
                slotData.courtId = courtId;
                slotData.courtName = courtName;
            });
            wizardState.selectedCourtId = courtId;
            wizardState.selectedCourtName = courtName;
        } else {
            return;
        }
    }

    // Check consecutive slot rule
    if (wizardState.selectedSlots.size > 0) {
        let selectedHoursList = [];
        wizardState.selectedSlots.forEach((val, key) => {
            selectedHoursList.push(parseInt(key.split(':')[0]));
        });
        selectedHoursList.sort((a, b) => a - b);

        let newSlotHr = parseInt(slotStart.split(':')[0]);
        let minHr = selectedHoursList[0];
        let maxHr = selectedHoursList[selectedHoursList.length - 1];

        // Slot must be adjacent (1 hr before min or 1 hr after max)
        if (newSlotHr !== (minHr - 1) && newSlotHr !== (maxHr + 1)) {
            alert("สำหรับการจองหลายชั่วโมง กรุณาเลือกช่วงเวลาที่ต่อเนื่องกัน (เช่น 14:00 - 15:00 และ 15:00 - 16:00)\nหรือคลิกยกเลิกการเลือกเดิมเพื่อเริ่มเลือกช่วงเวลาใหม่");
            return;
        }
    }

    // Add this slot
    wizardState.selectedSlots.set(slotStart, {
        courtId: courtId,
        courtName: courtName,
        price: price,
        slotStart: slotStart,
        slotEnd: slotEnd
    });
    wizardState.selectedCourtId = courtId;
    wizardState.selectedCourtName = courtName;

    renderCourtHourBlocks();
}

function updateStickySummaryBar() {
    let hours = wizardState.selectedSlots.size;
    wizardState.selectedHours = hours;

    let courtTotal = 0;
    let slotStarts = [];
    let slotEnds = [];
    let courtNames = new Set();

    wizardState.selectedSlots.forEach(s => {
        courtTotal += s.price;
        slotStarts.push(s.slotStart);
        slotEnds.push(s.slotEnd);
        courtNames.add(s.courtName);
    });

    wizardState.courtPriceTotal = courtTotal;

    // คำนวณช่วงเวลาเริ่มต้นและสิ้นสุด
    if (slotStarts.length > 0) {
        slotStarts.sort();
        slotEnds.sort();
        let earliestStart = slotStarts[0];
        let latestEnd = slotEnds[slotEnds.length - 1];

        let inputStart = document.getElementById('input_start_time');
        let inputEnd = document.getElementById('input_end_time');
        let inputCourt = document.getElementById('input_court_id');

        if (inputStart) inputStart.value = earliestStart;
        if (inputEnd) inputEnd.value = latestEnd;
        if (inputCourt) inputCourt.value = wizardState.selectedCourtId;
    }

    // อัปเดต Sticky Bar UI
    let hoursSpan = document.getElementById('stickyHoursDisplay');
    let courtSpan = document.getElementById('stickyCourtDisplay');
    let totalSpan = document.getElementById('stickyTotalDisplay');
    let btnNext = document.getElementById('btnStickyNext');

    if (hoursSpan) hoursSpan.innerText = `[ ${hours} Hour${hours > 1 ? 's' : ''} ]`;
    if (courtSpan) {
        let courtStr = Array.from(courtNames).join(', ');
        courtSpan.innerText = courtStr ? `[ ${courtStr} ]` : '[ ยังไม่ได้เลือกสนาม ]';
    }
    if (totalSpan) totalSpan.innerText = `[ Total ${courtTotal.toLocaleString()} ฿ ]`;

    if (btnNext) {
        btnNext.disabled = (hours === 0);
    }

    calculateGrandTotal();
}

function syncBookingNickname(val) {
    wizardState.bookingNickname = val.trim();
    let hiddenInput = document.getElementById('input_booking_nickname');
    if (hiddenInput) hiddenInput.value = wizardState.bookingNickname;
}

/* ==========================================================================
   Step 4: Optional Add-on Services (Quantities & Stepper)
   ========================================================================== */
function updateStep4Summary() {
    let stripDate = document.getElementById('stripDate');
    let stripTime = document.getElementById('stripTime');
    let stripCourt = document.getElementById('stripCourt');

    let inputStart = document.getElementById('input_start_time');
    let inputEnd = document.getElementById('input_end_time');

    if (stripDate) stripDate.innerText = `วันที่: ${formatDateThai(wizardState.selectedDate)}`;
    if (stripTime) stripTime.innerText = `เวลา: ${inputStart ? inputStart.value : ''} - ${inputEnd ? inputEnd.value : ''} (${wizardState.selectedHours} ชม.)`;
    if (stripCourt) stripCourt.innerText = `สนาม: ${wizardState.selectedCourtName || '-'}`;
}

function adjustProductQty(prodId, delta) {
    let input = document.getElementById('prod_qty_' + prodId);
    if (!input) return;

    let curVal = parseInt(input.value) || 0;
    let max = parseInt(input.getAttribute('max')) || 999;
    let newVal = curVal + delta;

    if (newVal < 0) newVal = 0;
    if (newVal > max) newVal = max;

    input.value = newVal;
    calculateGrandTotal();
}

function onProductQtyChange(prodId) {
    let input = document.getElementById('prod_qty_' + prodId);
    if (!input) return;

    let val = parseInt(input.value) || 0;
    let max = parseInt(input.getAttribute('max')) || 999;

    if (val < 0) val = 0;
    if (val > max) val = max;

    input.value = val;
    calculateGrandTotal();
}

/* ==========================================================================
   Grand Total Calculation & Step 5 Itemized Invoice
   ========================================================================== */
function calculateGrandTotal() {
    let rentTotal = 0;
    let prodTotal = 0;

    let qtyInputs = document.querySelectorAll('.qty-input');
    qtyInputs.forEach(input => {
        let qty = parseInt(input.value) || 0;
        let price = parseFloat(input.getAttribute('data-price')) || 0;
        let type = input.getAttribute('data-type');

        if (qty > 0) {
            if (type === 'อุปกรณ์เช่า') {
                rentTotal += (qty * price);
            } else {
                prodTotal += (qty * price);
            }
        }
    });

    wizardState.rentalPriceTotal = rentTotal;
    wizardState.productPriceTotal = prodTotal;
    wizardState.grandTotal = wizardState.courtPriceTotal + rentTotal + prodTotal;

    // Sync to hidden inputs
    let inputCourtPrice = document.getElementById('input_court_price');
    let inputRentPrice = document.getElementById('input_rental_price');
    let inputProdPrice = document.getElementById('input_product_price');
    let inputTotalPrice = document.getElementById('input_total_price');

    if (inputCourtPrice) inputCourtPrice.value = wizardState.courtPriceTotal;
    if (inputRentPrice) inputRentPrice.value = rentTotal;
    if (inputProdPrice) inputProdPrice.value = prodTotal;
    if (inputTotalPrice) inputTotalPrice.value = wizardState.grandTotal;
}

function updateInvoiceReview() {
    calculateGrandTotal();

    let invNick = document.getElementById('invNickname');
    let invDate = document.getElementById('invDate');
    let invTime = document.getElementById('invTime');
    let invCourt = document.getElementById('invCourt');
    let invGrand = document.getElementById('invGrandTotal');
    let tableBody = document.getElementById('invoiceTableBody');

    let inputStart = document.getElementById('input_start_time');
    let inputEnd = document.getElementById('input_end_time');

    let nick = wizardState.bookingNickname || 'ผู้ใช้งาน';
    if (invNick) invNick.innerText = nick;
    if (invDate) invDate.innerText = `${formatDateThai(wizardState.selectedDate)}`;
    if (invTime) invTime.innerText = `${inputStart ? inputStart.value : ''} - ${inputEnd ? inputEnd.value : ''} (${wizardState.selectedHours} ชั่วโมง)`;
    if (invCourt) invCourt.innerText = wizardState.selectedCourtName || '-';
    if (invGrand) invGrand.innerText = wizardState.grandTotal.toLocaleString() + ' ฿';

    if (tableBody) {
        let html = '';

        // 1. ค่าสนาม
        html += `
            <tr>
                <td><strong>ค่าบริการสนาม (${wizardState.selectedCourtName})</strong><br><small class="text-muted">${wizardState.dayRateLabel}</small></td>
                <td>${wizardState.selectedHours} ชม.</td>
                <td>${wizardState.dayRatePrice.toLocaleString()} ฿</td>
                <td>${wizardState.courtPriceTotal.toLocaleString()} ฿</td>
            </tr>
        `;

        // 2. สินค้าและอุปกรณ์เช่า
        let qtyInputs = document.querySelectorAll('.qty-input');
        qtyInputs.forEach(input => {
            let qty = parseInt(input.value) || 0;
            if (qty > 0) {
                let name = input.getAttribute('data-name');
                let price = parseFloat(input.getAttribute('data-price')) || 0;
                let type = input.getAttribute('data-type');
                let itemTotal = qty * price;

                html += `
                    <tr>
                        <td>${name} <small class="text-muted">(${type})</small></td>
                        <td>${qty} หน่วย</td>
                        <td>${price.toLocaleString()} ฿</td>
                        <td>${itemTotal.toLocaleString()} ฿</td>
                    </tr>
                `;
            }
        });

        tableBody.innerHTML = html;
    }
}

// Form Submission with Loading Spinner Feedback
function handleBookingFormSubmit(event) {
    if (wizardState.selectedSlots.size === 0) {
        alert("กรุณาเลือกสนามก่อนยืนยันการจอง");
        if (event) event.preventDefault();
        return false;
    }

    let btnSubmit = document.getElementById('btnFinalSubmitBooking');
    if (btnSubmit) {
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึกการจองและล็อกสนาม...';
    }

    return true;
}

/* ==========================================================================
   Payment Page Countdown Timer & Slip Upload (Existing features maintained)
   ========================================================================== */


/* =========================================
   Payment Countdown Timer (หน้าชำระเงิน)
   ========================================= */
function initPaymentCountdown(lockExpireStr) {
    let countdownElement = document.getElementById("countdown");
    if (!countdownElement || !lockExpireStr) return;

    let expireTime = new Date(lockExpireStr.replace(/-/g, "/")).getTime();

    let countdownTimer = setInterval(function() {
        let now = new Date().getTime();
        let distance = expireTime - now;

        if (distance < 0) {
            clearInterval(countdownTimer);
            countdownElement.innerText = "หมดเวลาการจอง!";
            alert("หมดเวลาชำระเงินภายใน 15 นาที ระบบจะยกเลิกการจองนี้อัตโนมัติ");
            window.location.href = "booking.php";
            return;
        }

        let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        let seconds = Math.floor((distance % (1000 * 60)) / 1000);

        countdownElement.innerText = 
            (minutes < 10 ? "0" + minutes : minutes) + ":" + (seconds < 10 ? "0" + seconds : seconds);
    }, 1000);
}

/* =========================================
   Payment Page - Slip Upload & Preview (HCI)
   ========================================= */
function initSlipUpload() {
    let fileInput = document.getElementById("paymentSlipInput");
    let uploadBox = document.getElementById("slipUploadBox");
    let uploadPrompt = document.getElementById("uploadPrompt");
    let previewContainer = document.getElementById("previewContainer");
    let previewImg = document.getElementById("slipPreviewImg");
    let fileInfoText = document.getElementById("fileInfoText");
    let btnChangeSlip = document.getElementById("btnChangeSlip");
    let errorMsg = document.getElementById("clientErrorMsg");
    let paymentForm = document.getElementById("paymentForm");
    let btnSubmit = document.getElementById("btnSubmitPayment");

    if (!fileInput || !uploadBox) return;

    // คลิกที่กล่องเพื่อเปิด File Dialog
    uploadBox.addEventListener("click", function(e) {
        if (e.target.closest("#btnChangeSlip") || e.target === fileInput) return;
        fileInput.click();
    });

    if (btnChangeSlip) {
        btnChangeSlip.addEventListener("click", function(e) {
            e.stopPropagation();
            fileInput.click();
        });
    }

    // Drag and Drop
    ["dragenter", "dragover"].forEach(eventName => {
        uploadBox.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
            uploadBox.classList.add("dragover");
        }, false);
    });

    ["dragleave", "drop"].forEach(eventName => {
        uploadBox.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
            uploadBox.classList.remove("dragover");
        }, false);
    });

    uploadBox.addEventListener("drop", function(e) {
        let dt = e.dataTransfer;
        let files = dt.files;
        if (files && files.length > 0) {
            fileInput.files = files;
            handleSlipFile(files[0]);
        }
    });

    // เมื่อเลือกไฟล์ผ่าน Dialog
    fileInput.addEventListener("change", function() {
        if (fileInput.files && fileInput.files.length > 0) {
            handleSlipFile(fileInput.files[0]);
        }
    });

    function handleSlipFile(file) {
        if (errorMsg) {
            errorMsg.style.display = "none";
            errorMsg.innerText = "";
        }

        // 1. ตรวจสอบประเภทไฟล์
        const allowedTypes = ["image/jpeg", "image/png", "image/webp"];
        const ext = file.name.split('.').pop().toLowerCase();
        const allowedExts = ["jpg", "jpeg", "png", "webp"];

        if (!allowedTypes.includes(file.type) && !allowedExts.includes(ext)) {
            showError("รูปแบบไฟล์ไม่ถูกต้อง! รองรับเฉพาะ JPG, PNG, WEBP เท่านั้น");
            resetUploadState();
            return;
        }

        // 2. ตรวจสอบขนาดไฟล์ (ไม่เกิน 5MB)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            showError("ขนาดไฟล์รูปภาพเกินกำหนด! (สูงสุด 5MB)");
            resetUploadState();
            return;
        }

        // 3. แสดงรูปพรีวิวด้วย FileReader
        let reader = new FileReader();
        reader.onload = function(e) {
            if (previewImg) previewImg.src = e.target.result;
            
            let sizeFormatted = file.size > 1024 * 1024 
                ? (file.size / (1024 * 1024)).toFixed(2) + " MB" 
                : (file.size / 1024).toFixed(1) + " KB";
            
            if (fileInfoText) {
                fileInfoText.innerText = file.name + " (" + sizeFormatted + ")";
            }

            if (uploadPrompt) uploadPrompt.style.display = "none";
            if (previewContainer) previewContainer.style.display = "flex";
        };
        reader.readAsDataURL(file);
    }

    function showError(msg) {
        if (errorMsg) {
            errorMsg.innerText = msg;
            errorMsg.style.display = "block";
        } else {
            alert(msg);
        }
    }

    function resetUploadState() {
        fileInput.value = "";
        if (previewImg) previewImg.src = "";
        if (uploadPrompt) uploadPrompt.style.display = "flex";
        if (previewContainer) previewContainer.style.display = "none";
    }

    // 4. Loading & Disabled State เมื่อ Submit Form
    if (paymentForm) {
        paymentForm.addEventListener("submit", function(e) {
            if (!fileInput.files || fileInput.files.length === 0) {
                e.preventDefault();
                showError("กรุณาแนบรูปภาพสลิปการโอนเงินก่อนยืนยัน");
                return false;
            }

            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังอัปโหลดสลิป กรุณารอสักครู่...';
                btnSubmit.style.opacity = "0.75";
                btnSubmit.style.cursor = "not-allowed";
            }
        });
    }
}

// ฟังก์ชันคัดลอกเลขบัญชีธนาคาร
function copyBankAccount(accNo) {
    if (!accNo) return;
    
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(accNo).then(showFeedback).catch(() => fallbackCopy(accNo));
    } else {
        fallbackCopy(accNo);
    }

    function fallbackCopy(text) {
        let tempInput = document.createElement("textarea");
        tempInput.value = text;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand("copy");
        document.body.removeChild(tempInput);
        showFeedback();
    }

    function showFeedback() {
        let feedback = document.getElementById("copyFeedback");
        if (feedback) {
            feedback.style.display = "inline";
            setTimeout(() => {
                feedback.style.display = "none";
            }, 2500);
        }
    }
}

/* ==========================================================================
   Rewards Page Controller (Tabs, Redemption Modal & Verification)
   ========================================================================== */

function switchRewardsTab(targetTab) {
    let tabButtons = document.querySelectorAll('.rewards-tab-btn');
    let tabPanes = document.querySelectorAll('.rewards-tab-pane');

    tabButtons.forEach(btn => {
        if (btn.getAttribute('data-tab') === targetTab) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    tabPanes.forEach(pane => {
        if (pane.id === targetTab) {
            pane.classList.add('active');
        } else {
            pane.classList.remove('active');
        }
    });
}

function openRedeemModal(rewardId, rewardName, pointCost, rewardImg, userPoints) {
    let modal = document.getElementById('rewardConfirmModal');
    if (!modal) return;

    // Set hidden form values
    let inputRewardId = document.getElementById('modal_reward_id');
    if (inputRewardId) inputRewardId.value = rewardId;

    // Set preview details
    let titleEl = document.getElementById('modal_reward_title');
    let costEl = document.getElementById('modal_reward_cost');
    let imgEl = document.getElementById('modal_reward_img');
    let iconEl = document.getElementById('modal_reward_placeholder');

    if (titleEl) titleEl.innerText = rewardName;
    if (costEl) costEl.innerHTML = '<i class="fas fa-coins text-warning"></i> ' + Number(pointCost).toLocaleString() + ' คะแนน';

    if (rewardImg && rewardImg.trim() !== '') {
        if (imgEl) {
            imgEl.src = 'uploads/rewards/' + rewardImg;
            imgEl.style.display = 'block';
        }
        if (iconEl) iconEl.style.display = 'none';
    } else {
        if (imgEl) imgEl.style.display = 'none';
        if (iconEl) iconEl.style.display = 'flex';
    }

    // Calculate points breakdown
    let currentPtsEl = document.getElementById('modal_current_points');
    let deductPtsEl = document.getElementById('modal_deduct_points');
    let remainPtsEl = document.getElementById('modal_remain_points');

    let current = Number(userPoints);
    let cost = Number(pointCost);
    let remaining = current - cost;

    if (currentPtsEl) currentPtsEl.innerText = current.toLocaleString() + ' คะแนน';
    if (deductPtsEl) deductPtsEl.innerText = '- ' + cost.toLocaleString() + ' คะแนน';
    if (remainPtsEl) remainPtsEl.innerText = remaining.toLocaleString() + ' คะแนน';

    // Show modal
    modal.classList.add('active');
}

function closeRedeemModal() {
    let modal = document.getElementById('rewardConfirmModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

function executeRedeemSubmit() {
    let btnSubmit = document.getElementById('btnModalConfirmRedeem');
    let form = document.getElementById('redeemRewardForm');

    if (btnSubmit) {
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังทำรายการ...';
        btnSubmit.style.opacity = '0.75';
        btnSubmit.style.cursor = 'not-allowed';
    }

    if (form) {
        form.submit();
    }
}