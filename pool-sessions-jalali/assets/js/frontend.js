/**
 * Pool Sessions Jalali - Frontend JavaScript
 * 
 * Handles the calendar display and interactions
 */

(function($) {
    'use strict';
    
    // Global variables
    let calendar;
    let currentYear, currentMonth, currentGender, currentService;
    
    // Initialize when document is ready
    $(document).ready(function() {
        initializeCalendar();
        bindEvents();
        loadInitialData();
    });
    
    /**
     * Initialize the FullCalendar
     */
    function initializeCalendar() {
        const container = document.getElementById('pool-calendar');
        if (!container) return;
        
        // Get initial data from container attributes
        const containerEl = document.getElementById('pool-calendar-container');
        currentYear = parseInt(containerEl.dataset.year) || 1404;
        currentMonth = parseInt(containerEl.dataset.month) || 6;
        currentGender = containerEl.dataset.gender || 'all';
        currentService = containerEl.dataset.service || '*';
        
        // Set initial values in controls
        $('#month-selector').val(currentMonth);
        $('#year-selector').val(currentYear);
        updateGenderButtons(currentGender);
        
        // Initialize FullCalendar
        calendar = new FullCalendar.Calendar(container, {
            initialView: 'dayGridMonth',
            locale: 'fa',
            direction: 'rtl',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,listWeek'
            },
            buttonText: {
                today: poolSessionsData.translations.today,
                month: poolSessionsData.translations.month,
                week: poolSessionsData.translations.week,
                list: poolSessionsData.translations.list
            },
            firstDay: poolSessionsData.options.week_start === 'saturday' ? 0 : 1,
            height: 'auto',
            expandRows: true,
            dayMaxEvents: true,
            moreLinkClick: 'popover',
            eventDisplay: 'block',
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: poolSessionsData.options.time_format === 'hh:mm A'
            },
            eventDidMount: function(info) {
                customizeEvent(info.event, info.el);
            },
            eventClick: function(info) {
                showEventDetails(info.event);
            },
            datesSet: function(info) {
                // Update current view dates
                const startDate = info.start;
                const gregorianYear = startDate.getFullYear();
                const gregorianMonth = startDate.getMonth() + 1;
                const gregorianDay = startDate.getDate();
                
                // Convert to Jalali
                const jalaliDate = gregorianToJalali(gregorianYear, gregorianMonth, gregorianDay);
                currentYear = jalaliDate.year;
                currentMonth = jalaliDate.month;
                
                // Update controls
                $('#month-selector').val(currentMonth);
                $('#year-selector').val(currentYear);
                
                // Load sessions for this month
                loadSessions(currentYear, currentMonth, currentGender, currentService);
            }
        });
        
        calendar.render();
    }
    
    /**
     * Bind event handlers
     */
    function bindEvents() {
        // Gender toggle buttons
        $('.gender-btn').on('click', function() {
            const gender = $(this).data('gender');
            currentGender = gender;
            updateGenderButtons(gender);
            loadSessions(currentYear, currentMonth, currentGender, currentService);
        });
        
        // Service selector
        $('#service-filter').on('change', function() {
            currentService = $(this).val();
            loadSessions(currentYear, currentMonth, currentGender, currentService);
        });
        
        // Month selector
        $('#month-selector').on('change', function() {
            currentMonth = parseInt($(this).val());
            navigateToMonth(currentYear, currentMonth);
        });
        
        // Year selector
        $('#year-selector').on('change', function() {
            currentYear = parseInt($(this).val());
            navigateToMonth(currentYear, currentMonth);
        });
        
        // Mobile gesture support
        if (poolSessionsData.options.enable_mobile_gestures) {
            enableMobileGestures();
        }
    }
    
    /**
     * Load initial data
     */
    function loadInitialData() {
        // Load services
        loadServices();
        
        // Load initial sessions
        loadSessions(currentYear, currentMonth, currentGender, currentService);
    }
    
    /**
     * Load sessions for a specific month
     */
    function loadSessions(year, month, gender, service) {
        // Show loading state
        showLoading(true);
        
        // Clear existing events
        calendar.removeAllEvents();
        
        // Build API URL
        const apiUrl = poolSessionsData.restUrl + 'sessions';
        const params = new URLSearchParams({
            year: year,
            month: month,
            gender: gender,
            service: service
        });
        
        // Make API request
        $.ajax({
            url: apiUrl + '?' + params.toString(),
            method: 'GET',
            headers: {
                'X-WP-Nonce': poolSessionsData.nonce
            },
            success: function(data) {
                if (data && Array.isArray(data)) {
                    addEventsToCalendar(data);
                } else {
                    showMessage(poolSessionsData.translations.noSessions, 'info');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading sessions:', error);
                showMessage(poolSessionsData.translations.error, 'error');
            },
            complete: function() {
                showLoading(false);
            }
        });
    }
    
    /**
     * Load available services
     */
    function loadServices() {
        const apiUrl = poolSessionsData.restUrl + 'services';
        
        $.ajax({
            url: apiUrl,
            method: 'GET',
            headers: {
                'X-WP-Nonce': poolSessionsData.nonce
            },
            success: function(data) {
                if (data && Array.isArray(data)) {
                    populateServiceSelector(data);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading services:', error);
            }
        });
    }
    
    /**
     * Populate service selector
     */
    function populateServiceSelector(services) {
        const selector = $('#service-filter');
        selector.empty();
        
        // Add "All Services" option
        selector.append('<option value="*">' + poolSessionsData.translations.allServices + '</option>');
        
        // Add service options
        services.forEach(function(service) {
            const option = $('<option></option>')
                .val(service.name)
                .text(service.name);
            
            if (service.color) {
                option.css('color', service.color);
            }
            
            selector.append(option);
        });
        
        // Set current service
        if (currentService !== '*') {
            selector.val(currentService);
        }
    }
    
    /**
     * Add events to calendar
     */
    function addEventsToCalendar(sessions) {
        const events = sessions.map(function(session) {
            return {
                id: session.id,
                title: formatEventTitle(session),
                start: session.start,
                end: session.end,
                extendedProps: {
                    service: session.service,
                    gender: session.gender,
                    note: session.note,
                    capacity: session.capacity
                },
                classNames: ['fc-event', session.gender]
            };
        });
        
        calendar.addEventSource(events);
    }
    
    /**
     * Format event title
     */
    function formatEventTitle(session) {
        const startTime = formatTime(session.start);
        const endTime = formatTime(session.end);
        return `${startTime}–${endTime} • ${session.service}`;
    }
    
    /**
     * Format time for display
     */
    function formatTime(datetime) {
        const date = new Date(datetime);
        const hours = date.getHours().toString().padStart(2, '0');
        const minutes = date.getMinutes().toString().padStart(2, '0');
        
        if (poolSessionsData.options.time_format === 'hh:mm A') {
            const hour12 = date.getHours() % 12 || 12;
            const ampm = date.getHours() >= 12 ? 'PM' : 'AM';
            return `${hour12}:${minutes} ${ampm}`;
        }
        
        return `${hours}:${minutes}`;
    }
    
    /**
     * Customize event appearance
     */
    function customizeEvent(event, element) {
        const gender = event.extendedProps.gender;
        const service = event.extendedProps.service;
        
        // Apply gender-based styling
        element.classList.add(gender);
        
        // Apply service-based color if available
        const serviceColor = getServiceColor(service);
        if (serviceColor) {
            element.style.backgroundColor = serviceColor;
            element.style.borderColor = adjustColor(serviceColor, -20);
        }
        
        // Add tooltip if enabled
        if (poolSessionsData.options.show_tooltip && event.extendedProps.note) {
            element.setAttribute('title', event.extendedProps.note);
        }
    }
    
    /**
     * Get service color
     */
    function getServiceColor(serviceName) {
        if (poolSessionsData.options.service_colors && poolSessionsData.options.service_colors[serviceName]) {
            return poolSessionsData.options.service_colors[serviceName];
        }
        return null;
    }
    
    /**
     * Adjust color brightness
     */
    function adjustColor(color, amount) {
        const usePound = color[0] === '#';
        const col = usePound ? color.slice(1) : color;
        const num = parseInt(col, 16);
        let r = (num >> 16) + amount;
        let g = (num >> 8 & 0x00FF) + amount;
        let b = (num & 0x0000FF) + amount;
        
        r = r > 255 ? 255 : r < 0 ? 0 : r;
        g = g > 255 ? 255 : g < 0 ? 0 : g;
        b = b > 255 ? 255 : b < 0 ? 0 : b;
        
        return (usePound ? '#' : '') + (g | (b << 8) | (r << 16)).toString(16).padStart(6, '0');
    }
    
    /**
     * Show event details
     */
    function showEventDetails(event) {
        const props = event.extendedProps;
        const startTime = formatTime(event.start);
        const endTime = formatTime(event.end);
        
        let details = `
            <div class="event-details">
                <h3>${event.title}</h3>
                <p><strong>Service:</strong> ${props.service}</p>
                <p><strong>Gender:</strong> ${props.gender}</p>
                <p><strong>Time:</strong> ${startTime} - ${endTime}</p>
        `;
        
        if (props.note) {
            details += `<p><strong>Note:</strong> ${props.note}</p>`;
        }
        
        if (props.capacity) {
            details += `<p><strong>Capacity:</strong> ${props.capacity}</p>`;
        }
        
        details += '</div>';
        
        // Show in a modal or tooltip
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: event.title,
                html: details,
                confirmButtonText: 'Close'
            });
        } else {
            alert(details);
        }
    }
    
    /**
     * Navigate to specific month
     */
    function navigateToMonth(year, month) {
        // Convert Jalali to Gregorian for FullCalendar
        const jalaliDate = jalaliToGregorian(year, month, 1);
        const gregorianDate = new Date(jalaliDate.year, jalaliDate.month - 1, jalaliDate.day);
        
        calendar.gotoDate(gregorianDate);
    }
    
    /**
     * Update gender button states
     */
    function updateGenderButtons(activeGender) {
        $('.gender-btn').removeClass('active');
        $(`.gender-btn[data-gender="${activeGender}"]`).addClass('active');
    }
    
    /**
     * Show/hide loading state
     */
    function showLoading(show) {
        if (show) {
            $('#pool-calendar').addClass('loading');
            // Add loading spinner if needed
        } else {
            $('#pool-calendar').removeClass('loading');
        }
    }
    
    /**
     * Show message
     */
    function showMessage(message, type = 'info') {
        // Implement message display (toast, alert, etc.)
        console.log(`[${type.toUpperCase()}] ${message}`);
    }
    
    /**
     * Enable mobile gestures
     */
    function enableMobileGestures() {
        let startX = 0;
        let startY = 0;
        
        $('#pool-calendar-container').on('touchstart', function(e) {
            startX = e.originalEvent.touches[0].clientX;
            startY = e.originalEvent.touches[0].clientY;
        });
        
        $('#pool-calendar-container').on('touchend', function(e) {
            if (!startX || !startY) return;
            
            const endX = e.originalEvent.changedTouches[0].clientX;
            const endY = e.originalEvent.changedTouches[0].clientY;
            
            const diffX = startX - endX;
            const diffY = startY - endY;
            
            // Horizontal swipe for month navigation
            if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
                if (diffX > 0) {
                    // Swipe left - next month
                    calendar.next();
                } else {
                    // Swipe right - previous month
                    calendar.prev();
                }
            }
            
            startX = 0;
            startY = 0;
        });
    }
    
    /**
     * Convert Gregorian to Jalali date
     */
    function gregorianToJalali(year, month, day) {
        // This is a simplified conversion - in production, use a proper library
        // For now, return approximate values
        return {
            year: year - 621,
            month: month,
            day: day
        };
    }
    
    /**
     * Convert Jalali to Gregorian date
     */
    function jalaliToGregorian(year, month, day) {
        // This is a simplified conversion - in production, use a proper library
        // For now, return approximate values
        return {
            year: year + 621,
            month: month,
            day: day
        };
    }
    
})(jQuery);
