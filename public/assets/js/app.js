(() => {
  'use strict';

  const API_URL = '/api/schedule.php';
  const ERROR_MESSAGE = '予定を取得できませんでした。';
  const MINUTES_PER_HOUR = 60;
  const SLOT_MINUTES = 30;
  const WEEKDAYS = ['日', '月', '火', '水', '木', '金', '土'];
  const VIEWS = {
    loading: 'loading',
    error: 'error',
    calendar: 'calendar',
  };

  const elements = {
    calendar: document.querySelector('#calendar'),
    calendarContainer: document.querySelector('#calendar-scroll'),
    status: document.querySelector('#status'),
    error: document.querySelector('#error'),
    errorMessage: document.querySelector('#error-message'),
    retry: document.querySelector('#retry'),
  };

  const parseDate = (value) => {
    const [year, month, day] = value.split('-').map(Number);
    return new Date(Date.UTC(year, month - 1, day));
  };

  const toMinutes = (time) => {
    const [hours, minutes] = time.split(':').map(Number);
    return (hours * MINUTES_PER_HOUR) + minutes;
  };

  // APIから受け取った文字列をエスケープしてHTMLへ埋め込む
  const escapeHtml = (value) => String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

  const formatDate = (dateKey) => {
    const date = parseDate(dateKey);
    return `${date.getUTCMonth() + 1}/${date.getUTCDate()}（${WEEKDAYS[date.getUTCDay()]}）`;
  };

  const renderHeader = (dateKeys) => dateKeys
    .map((dateKey) => `<div class="day-heading">${formatDate(dateKey)}</div>`)
    .join('');

  const renderTimeLabels = (startMinutes, endMinutes) => {
    const labels = [];

    for (let time = startMinutes; time <= endMinutes; time += MINUTES_PER_HOUR) {
      const hour = String(Math.floor(time / MINUTES_PER_HOUR)).padStart(2, '0');
      labels.push(`<span class="time-label">${hour}:00</span>`);
    }

    return labels.join('');
  };

  const renderMeeting = (meeting) => `
    <article class="meeting">
      <span class="meeting-label">${escapeHtml(meeting.summary)}</span>
    </article>
  `;

  // 予定を開始時刻に対応する30分単位のスロットへ配置する
  const renderDayColumns = (dateKeys, meetings, startMinutes, slotCount) => dateKeys
    .map((dateKey) => {
      const slots = Array.from({ length: slotCount }, () => []);

      meetings[dateKey].forEach((meeting) => {
        const slotIndex = (toMinutes(meeting.start) - startMinutes) / SLOT_MINUTES;
        if (Number.isInteger(slotIndex) && slots[slotIndex]) {
          slots[slotIndex].push(renderMeeting(meeting));
        }
      });

      const slotElements = slots
        .map((slot) => `<div class="meeting-slot">${slot.join('')}</div>`)
        .join('');

      return `<div class="day-column">${slotElements}</div>`;
    })
    .join('');

  // APIレスポンスからヘッダー、時刻列、日付列をまとめて描画する
  const renderCalendar = (data) => {
    const dateKeys = Object.keys(data.meetings).sort();
    const startMinutes = toMinutes(data.working_hours.start);
    const endMinutes = toMinutes(data.working_hours.end);
    const hourCount = ((endMinutes - startMinutes) / MINUTES_PER_HOUR) + 1;
    const slotCount = hourCount * (MINUTES_PER_HOUR / SLOT_MINUTES);

    elements.calendar.innerHTML = `
      <header class="calendar-header">
        <div></div>
        ${renderHeader(dateKeys)}
      </header>
      <div class="calendar-body">
        <div class="time-column">
          ${renderTimeLabels(startMinutes, endMinutes)}
        </div>
        ${renderDayColumns(dateKeys, data.meetings, startMinutes, slotCount)}
      </div>
    `;
  };

  // 読み込み中、エラー、カレンダーのうち必要な表示だけを有効にする
  const show = (view, message = '') => {
    elements.status.hidden = view !== VIEWS.loading;
    elements.error.hidden = view !== VIEWS.error;
    elements.calendarContainer.hidden = view !== VIEWS.calendar;

    if (message) {
      elements.errorMessage.textContent = message;
    }
  };

  // PHP側のAPIからスケジュールを取得し、結果に応じて表示を切り替える
  const loadSchedule = async () => {
    show(VIEWS.loading);

    try {
      const response = await fetch(API_URL);
      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.error || ERROR_MESSAGE);
      }

      renderCalendar(data);
      show(VIEWS.calendar);
    } catch (error) {
      show(VIEWS.error, error instanceof Error ? error.message : ERROR_MESSAGE);
    }
  };

  elements.retry.addEventListener('click', loadSchedule);
  loadSchedule();
})();
