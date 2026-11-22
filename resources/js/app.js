const arrowRefreshers = [];

const refreshArrowPanes = () => {
    arrowRefreshers.forEach((update) => update());
};

const bindArrowPanes = (root = document) => {
    root.querySelectorAll('[data-arrow-pane]').forEach((pane) => {
        if (pane.dataset.arrowBound === 'true') {
            return;
        }

        const scroller = pane.querySelector('[data-arrow-scroll]');
        const up = pane.querySelector('[data-scroll-up]');
        const down = pane.querySelector('[data-scroll-down]');

        if (! scroller || ! up || ! down) {
            return;
        }

        const update = () => {
            const overflow = scroller.scrollHeight - scroller.clientHeight > 2;

            up.hidden = ! overflow || scroller.scrollTop <= 2;
            down.hidden = ! overflow || scroller.scrollTop + scroller.clientHeight >= scroller.scrollHeight - 2;
        };

        const move = (direction) => {
            scroller.scrollBy({
                top: direction * Math.max(72, scroller.clientHeight * 0.7),
                behavior: 'smooth',
            });
        };

        up.addEventListener('click', () => move(-1));
        down.addEventListener('click', () => move(1));
        scroller.addEventListener('scroll', update);
        arrowRefreshers.push(update);
        pane.dataset.arrowBound = 'true';
        update();
    });
};

bindArrowPanes();

const arrowObserver = new ResizeObserver(() => refreshArrowPanes());

document.querySelectorAll('[data-arrow-scroll]').forEach((scroller) => arrowObserver.observe(scroller));

window.refreshDeskArrowPanes = refreshArrowPanes;

const stage = document.getElementById('desk-stage');
const dock = document.getElementById('desk-dock');

if (stage && dock) {
    let layer = 30;

    const windows = new Map();

    const remember = (win) => {
        win.dataset.left = win.style.left;
        win.dataset.top = win.style.top;
        win.dataset.width = win.style.width;
        win.dataset.height = win.style.height;
    };

    const stageBox = () => stage.getBoundingClientRect();

    const place = (win) => {
        const box = stageBox();
        const gutter = box.width < 640 ? 10 : 20;
        const maxWidth = Math.max(280, box.width - gutter * 2);
        const maxHeight = Math.max(280, box.height - gutter * 2);
        const width = box.width < 720
            ? maxWidth
            : Math.min(maxWidth, Math.max(640, Math.round(maxWidth * 0.78)));

        win.hidden = false;
        win.style.width = `${width}px`;
        win.style.height = `${Math.min(maxHeight, 640)}px`;

        const scroller = win.querySelector('[data-arrow-scroll]');
        const body = scroller?.firstElementChild;
        const bar = win.querySelector('.desk-window-bar');
        const contentHeight = body?.scrollHeight || scroller?.scrollHeight || 420;
        const chrome = (bar?.offsetHeight || 40) + 24;
        const height = Math.min(maxHeight, Math.max(box.width < 640 ? 400 : 560, contentHeight + chrome));

        win.style.height = `${height}px`;
        win.style.left = `${box.left + Math.max(0, (box.width - width) / 2)}px`;
        win.style.top = `${box.top + Math.max(0, (box.height - height) / 2)}px`;
        remember(win);
    };

    const fitMax = (win) => {
        const box = stageBox();

        win.style.left = `${box.left}px`;
        win.style.top = `${box.top}px`;
        win.style.width = `${box.width}px`;
        win.style.height = `${box.height}px`;
    };

    const raise = (win) => {
        layer += 1;
        win.style.zIndex = String(layer);
    };

    const chipFor = (win) => {
        let chip = dock.querySelector(`[data-dock="${win.id}"]`);

        if (chip) {
            return chip;
        }

        chip = document.createElement('button');
        chip.type = 'button';
        chip.dataset.dock = win.id;
        chip.className = 'shrink-0 rounded-xl bg-primary px-3 py-1 text-sm font-semibold';
        chip.textContent = win.dataset.title;
        chip.addEventListener('click', () => {
            if (win.hidden || win.classList.contains('is-max')) {
                restore(win);
            } else {
                raise(win);
            }
        });
        dock.appendChild(chip);

        return chip;
    };

    const restore = (win) => {
        win.hidden = false;
        win.classList.remove('is-max');

        if (win.dataset.left) {
            win.style.left = win.dataset.left;
            win.style.top = win.dataset.top;
            win.style.width = win.dataset.width;
            win.style.height = win.dataset.height;
        }

        setMaxIcons(win, false);
        raise(win);
        requestAnimationFrame(refreshArrowPanes);
    };

    const setMaxIcons = (win, maximized) => {
        const maxButton = win.querySelector('[data-window-max]');
        const maxIcon = win.querySelector('[data-window-max-icon]');
        const restoreIcon = win.querySelector('[data-window-restore-icon]');

        if (! maxButton || ! maxIcon || ! restoreIcon) {
            return;
        }

        maxButton.setAttribute('aria-label', maximized ? 'Restore' : 'Maximize');
        maxIcon.hidden = maximized;
        restoreIcon.hidden = ! maximized;
    };

    const minimize = (win) => {
        if (!win.classList.contains('is-max')) {
            remember(win);
        }

        win.classList.remove('is-max');
        win.hidden = true;
        setMaxIcons(win, false);
        chipFor(win);
    };

    const maximize = (win) => {
        if (!win.classList.contains('is-max')) {
            remember(win);
        }

        win.hidden = false;
        win.classList.add('is-max');
        fitMax(win);
        chipFor(win);
        setMaxIcons(win, true);
        raise(win);
        requestAnimationFrame(refreshArrowPanes);
    };

    const close = (win) => {
        win.hidden = true;
        win.classList.remove('is-max');
        setMaxIcons(win, false);
        dock.querySelector(`[data-dock="${win.id}"]`)?.remove();
        delete win.dataset.placed;
        delete win.dataset.left;
        delete win.dataset.top;
        delete win.dataset.width;
        delete win.dataset.height;
    };

    const open = (win) => {
        win.hidden = false;

        if (! win.dataset.placed) {
            place(win);
            win.dataset.placed = 'true';
        }

        restore(win);
        chipFor(win);
        requestAnimationFrame(() => {
            if (! win.classList.contains('is-max') && win.dataset.placed === 'true') {
                const box = stageBox();
                const scroller = win.querySelector('[data-arrow-scroll]');
                const body = scroller?.firstElementChild;
                const bar = win.querySelector('.desk-window-bar');
                const maxHeight = Math.max(280, box.height - (box.width < 640 ? 16 : 24));
                const contentHeight = body?.scrollHeight || scroller?.scrollHeight || 0;
                const chrome = (bar?.offsetHeight || 40) + 24;
                const fitted = Math.min(maxHeight, Math.max(parseFloat(win.style.height) || 0, contentHeight + chrome));

                if (fitted > (parseFloat(win.style.height) || 0) + 2) {
                    win.style.height = `${fitted}px`;
                    win.style.top = `${box.top + Math.max(0, (box.height - fitted) / 2)}px`;
                    remember(win);
                }
            }

            refreshArrowPanes();
        });
    };

    document.querySelectorAll('[data-window]').forEach((win) => {
        windows.set(win.id, win);

        win.querySelector('[data-window-min]')?.addEventListener('click', () => minimize(win));
        win.querySelector('[data-window-max]')?.addEventListener('click', () => {
            if (win.classList.contains('is-max')) {
                restore(win);
            } else {
                maximize(win);
            }
        });
        win.querySelectorAll('[data-window-close]').forEach((button) => {
            button.addEventListener('click', () => close(win));
        });

        const bar = win.querySelector('[data-window-drag]');

        bar?.addEventListener('pointerdown', (event) => {
            if (event.target.closest('button, a') || win.classList.contains('is-max')) {
                return;
            }

            const startX = event.clientX;
            const startY = event.clientY;
            const startLeft = parseFloat(win.style.left);
            const startTop = parseFloat(win.style.top);

            bar.setPointerCapture(event.pointerId);
            raise(win);

            const move = (moveEvent) => {
                win.style.left = `${startLeft + moveEvent.clientX - startX}px`;
                win.style.top = `${startTop + moveEvent.clientY - startY}px`;
            };

            const stop = () => {
                bar.removeEventListener('pointermove', move);
                bar.removeEventListener('pointerup', stop);
            };

            bar.addEventListener('pointermove', move);
            bar.addEventListener('pointerup', stop);
        });

        win.querySelectorAll('[data-window-resize]').forEach((handle) => {
            handle.addEventListener('pointerdown', (event) => {
                if (win.classList.contains('is-max')) {
                    return;
                }

                event.preventDefault();
                const edge = handle.dataset.windowResize;
                const startX = event.clientX;
                const startY = event.clientY;
                const startWidth = win.getBoundingClientRect().width;
                const startHeight = win.getBoundingClientRect().height;

                handle.setPointerCapture(event.pointerId);
                raise(win);

                const move = (moveEvent) => {
                    if (edge.includes('e')) {
                        win.style.width = `${Math.max(360, startWidth + moveEvent.clientX - startX)}px`;
                    }

                    if (edge.includes('s')) {
                        win.style.height = `${Math.max(360, startHeight + moveEvent.clientY - startY)}px`;
                    }
                };

                const stop = () => {
                    handle.removeEventListener('pointermove', move);
                    handle.removeEventListener('pointerup', stop);
                    refreshArrowPanes();
                };

                handle.addEventListener('pointermove', move);
                handle.addEventListener('pointerup', stop);
            });
        });

        win.addEventListener('pointerdown', () => raise(win));
        win.querySelector('[data-arrow-scroll]') && arrowObserver.observe(win.querySelector('[data-arrow-scroll]'));

        if (!win.hidden) {
            open(win);
        }
    });

    document.querySelectorAll('[data-window-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const win = windows.get(button.dataset.windowOpen);

            if (win) {
                open(win);
            }
        });
    });

    document.querySelectorAll('[data-preview-form]').forEach((form) => {
        form.addEventListener('submit', (event) => event.preventDefault());
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        const openWindows = [...windows.values()].filter((win) => !win.hidden);

        if (openWindows.length === 0) {
            return;
        }

        close(openWindows[openWindows.length - 1]);
    });

    const stageObserver = new ResizeObserver(() => {
        windows.forEach((win) => {
            if (win.classList.contains('is-max') && !win.hidden) {
                fitMax(win);
            }
        });
    });

    stageObserver.observe(stage);
}

const inkFor = (hex) => {
    const color = Number.parseInt(hex.replace('#', ''), 16);
    const light = (((color >> 16) & 255) * 299 + ((color >> 8) & 255) * 587 + (color & 255) * 114) / 1000;

    return light > 150 ? '#3a322b' : '#f7f6f2';
};

const markScheme = (id) => {
    document.querySelectorAll('[data-scheme-id]').forEach((button) => {
        const pressed = button.dataset.schemeId === String(id);

        button.setAttribute('aria-pressed', pressed ? 'true' : 'false');
        button.textContent = pressed ? 'Applied' : 'Apply';
    });
};

const applyScheme = (button) => {
    const scheme = {
        id: button.dataset.schemeId,
        page: button.dataset.page,
        card: button.dataset.card,
        soft: button.dataset.soft,
        line: button.dataset.line,
        primary: button.dataset.primary,
    };
    const root = document.documentElement;

    root.style.setProperty('--color-canvas', scheme.page);
    root.style.setProperty('--color-card', scheme.card);
    root.style.setProperty('--color-soft', scheme.soft);
    root.style.setProperty('--color-line', scheme.line);
    root.style.setProperty('--color-primary', scheme.primary);
    root.style.setProperty('--color-ink', inkFor(scheme.page));
    localStorage.setItem('desk-scheme', JSON.stringify(scheme));
    markScheme(scheme.id);
};

const clearScheme = () => {
    const root = document.documentElement;

    ['--color-canvas', '--color-card', '--color-soft', '--color-line', '--color-primary', '--color-ink'].forEach((name) => {
        root.style.removeProperty(name);
    });
    localStorage.removeItem('desk-scheme');
};

document.querySelectorAll('[data-scheme-id]').forEach((button) => {
    button.addEventListener('click', () => applyScheme(button));
});

const markCorners = (value) => {
    document.querySelectorAll('[data-corner]').forEach((button) => {
        button.setAttribute('aria-pressed', button.dataset.corner === value ? 'true' : 'false');
    });
};

const applyCorners = (value) => {
    const root = document.documentElement;

    if (value === 'round') {
        delete root.dataset.corners;
        localStorage.removeItem('desk-corners');
    } else {
        root.dataset.corners = value;
        localStorage.setItem('desk-corners', value);
    }

    markCorners(value);
};

document.querySelectorAll('[data-corner]').forEach((button) => {
    button.addEventListener('click', () => applyCorners(button.dataset.corner));
});

markCorners(document.documentElement.dataset.corners || 'round');

const savedScheme = localStorage.getItem('desk-scheme');
const schemeList = document.querySelector('[data-scheme-list]');

if (savedScheme) {
    try {
        const saved = JSON.parse(savedScheme);

        if (schemeList) {
            const button = schemeList.querySelector(`[data-scheme-id="${CSS.escape(String(saved.id))}"]`);

            if (button) {
                applyScheme(button);
            } else {
                clearScheme();
            }
        } else {
            markScheme(saved.id);
        }
    } catch (error) {
        localStorage.removeItem('desk-scheme');
    }
}

document.querySelectorAll('[data-copy-note]').forEach((button) => {
    button.addEventListener('click', async () => {
        const text = button.closest('tr')?.querySelector('[data-note-body]')?.textContent ?? '';

        try {
            await navigator.clipboard.writeText(text);
        } catch (error) {
            const area = document.createElement('textarea');

            area.value = text;
            area.setAttribute('readonly', '');
            area.style.position = 'fixed';
            area.style.left = '-9999px';
            document.body.appendChild(area);
            area.select();

            const copied = document.execCommand('copy');

            area.remove();

            if (! copied) {
                return;
            }
        }

        const previous = button.textContent;

        button.textContent = 'Copied';
        window.setTimeout(() => {
            button.textContent = previous;
        }, 1200);
    });
});

document.querySelectorAll('[data-color-pair]').forEach((pair) => {
    const picker = pair.querySelector('[type="color"]');
    const text = pair.querySelector('[type="text"]');

    picker?.addEventListener('input', () => {
        text.value = picker.value;
    });

    text?.addEventListener('input', () => {
        if (/^#[0-9a-fA-F]{6}$/.test(text.value)) {
            picker.value = text.value;
        }
    });
});
