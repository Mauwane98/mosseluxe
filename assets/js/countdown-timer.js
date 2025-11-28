/**
 * Countdown Timer Component
 * Display time-limited offer countdowns
 */

class CountdownTimer {
    constructor(element, endTime) {
        this.element = element;
        this.endTime = new Date(endTime).getTime();
        this.interval = null;
        this.init();
    }

    init() {
        this.update();
        this.interval = setInterval(() => this.update(), 1000);
    }

    update() {
        const now = new Date().getTime();
        const distance = this.endTime - now;

        if (distance < 0) {
            this.expired();
            return;
        }

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        this.render(days, hours, minutes, seconds);
    }

    render(days, hours, minutes, seconds) {
        const html = `
            <div class="flex gap-2 justify-center items-center">
                ${days > 0 ? `
                    <div class="flex flex-col items-center bg-black text-white rounded-lg px-3 py-2 min-w-[60px]">
                        <span class="text-2xl font-bold">${this.pad(days)}</span>
                        <span class="text-xs uppercase">Days</span>
                    </div>
                ` : ''}
                <div class="flex flex-col items-center bg-black text-white rounded-lg px-3 py-2 min-w-[60px]">
                    <span class="text-2xl font-bold">${this.pad(hours)}</span>
                    <span class="text-xs uppercase">Hours</span>
                </div>
                <div class="text-2xl font-bold">:</div>
                <div class="flex flex-col items-center bg-black text-white rounded-lg px-3 py-2 min-w-[60px]">
                    <span class="text-2xl font-bold">${this.pad(minutes)}</span>
                    <span class="text-xs uppercase">Mins</span>
                </div>
                <div class="text-2xl font-bold">:</div>
                <div class="flex flex-col items-center bg-black text-white rounded-lg px-3 py-2 min-w-[60px]">
                    <span class="text-2xl font-bold">${this.pad(seconds)}</span>
                    <span class="text-xs uppercase">Secs</span>
                </div>
            </div>
        `;
        
        this.element.innerHTML = html;
    }

    pad(num) {
        return num < 10 ? '0' + num : num;
    }

    expired() {
        clearInterval(this.interval);
        this.element.innerHTML = `
            <div class="text-center py-4">
                <p class="text-red-600 font-bold text-lg">Sale Ended</p>
                <p class="text-gray-600 text-sm">This offer has expired</p>
            </div>
        `;
        
        // Trigger page reload after 3 seconds
        setTimeout(() => {
            window.location.reload();
        }, 3000);
    }

    destroy() {
        if (this.interval) {
            clearInterval(this.interval);
        }
    }
}

// Initialize all countdown timers on page load
document.addEventListener('DOMContentLoaded', () => {
    const timers = document.querySelectorAll('[data-countdown]');
    
    timers.forEach(timer => {
        const endTime = timer.dataset.countdown;
        if (endTime) {
            new CountdownTimer(timer, endTime);
        }
    });
});

// Export for use in other scripts
window.CountdownTimer = CountdownTimer;
