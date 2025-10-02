// Sprawdzanie, czy arkusz stylów już istnieje
if (!document.querySelector('link[href="style.css"]')) {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'style.css';
    document.head.appendChild(link);
}

// Dodanie stylów dla klasy wind-arrow
if (!document.querySelector('style#wind-arrow-style')) {
    const style = document.createElement('style');
    style.id = 'wind-arrow-style';
    style.textContent = `
        .wind-arrow {
            background-color: transparent !important;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .wind-arrow svg {
            width: 30px;
            height: 30px;
        }
    `;
    document.head.appendChild(style);
}

function description(x, unit) {
    console.log(unit);

    // Sprawdzamy, czy elementy istnieją
    const elements = {
        temperature: document.getElementById('temperature'),
        humidity: document.getElementById('humidity'),
        wind: document.getElementById('wind'),
        description: document.getElementById('description'),
        weatherIcon: document.getElementById('weather-icon'),
        date: document.getElementById('date')
    };

    if (!elements.temperature || !elements.humidity || !elements.wind || !elements.description || !elements.weatherIcon || !elements.date) {
        console.warn('Warning: One or more required HTML elements (temperature, humidity, wind, description, weather-icon, date) are missing.');
        return;
    }

    elements.temperature.innerHTML = `Temperatura: ${Number.parseInt(x.temp_display)}${unit === 'metric' ? '°C' : unit === 'imperial' ? 'K' : ''}`;
    elements.humidity.innerHTML = `Wilgotność: ${x.humidity}%`;
    elements.wind.innerHTML = `Wiatr: ${x.wind_display}${unit === 'metric' ? 'km/h' : unit === 'imperial' ? 'mph' : ''} `;
    elements.description.innerHTML = `${x.description}`;
    document.getElementById('temp-description').innerHTML= `${Number.parseInt(x.temp_display)}`
    let iconUrl = `http://openweathermap.org/img/wn/${x.icon}@2x.png`;
    elements.weatherIcon.src = iconUrl;
    const data = new Date((x.datedt + x.timezone) * 1000);
    const options = { weekday: 'long', hour: '2-digit', minute: '2-digit' };
    elements.date.innerHTML = data.toLocaleString('pl-PL', options);
}

function plot(unit,parameter, data) {
   console.log(parameter);
    // if (!Array.isArray(data) || data.length === 0) {
    //     console.error('Error: Data is not an array or is empty');
    //     return;
    // }

    // Wywołanie description z pierwszym elementem tablicy data
    if (data.length > 0) {
        description(data[0], unit);
    }

    // Maksymalna wysokość diva w pikselach
    const maxHeight = 150;
    let heightCalculation;
    let divClass = '';

    if (parameter === 'temp') {
        var hasNegativeValue = data.some(obj => obj.temp < 0);
        if (hasNegativeValue) {
            var minValue = Math.min(...data.map(obj => obj.temp));
            data.forEach(obj => {
                obj.temp_plot = obj.temp + minValue * -1;
            });
        }

        // Wybór pola do rysowania (temp_plot jeśli istnieje, inaczej temp)
        const valueField = data.some(obj => obj.temp_plot !== undefined) ? 'temp_plot' : 'temp';

        // Obliczenie minimalnej i maksymalnej wartości
        const values = data.map(obj => obj[valueField]);
        const minVal = Math.min(...values);
        const maxVal = Math.max(...values);
        const range = maxVal - minVal || 1; // Unikamy dzielenia przez 0

        // Obliczenie wysokości dla temp
        heightCalculation = obj => ((obj[valueField] - minVal) / range) * maxHeight + 5;
    } else if (parameter === 'pop') {
        // Obliczenie wysokości dla pop: maxHeight * pop + 5
        heightCalculation = obj => maxHeight * obj.pop + 5;
    } else if (parameter === 'wind_speed') {
        // Stała wysokość 150px, klasa wind-arrow
        heightCalculation = obj => maxHeight;
        divClass = 'wind-arrow';
    } else {
        console.error('Error: Unknown parameter:', parameter);
        return;
    }

    // Generowanie divów
    const divs = data.map(obj => {
        const height = heightCalculation ? heightCalculation(obj) : 0;
        const content = parameter === 'wind_speed' ? `
        <svg style="transform: rotate(${obj.wind_deg}deg);" viewBox="0 0 40 40" shape-rendering="geometricPrecision" text-rendering="geometricPrecision"><line x1="6" y1="-6" x2="-6" y2="6" transform="translate(14 6)" fill="none" stroke="#3f5787" stroke-width="1.3"/><line x1="0" y1="-20" x2="0" y2="20" transform="translate(20 20)" fill="none" stroke="#3f5787" stroke-width="1.3"/><line x1="-7" y1="-6" x2="7" y2="6" transform="translate(27, 6)" fill="none" stroke="#3f5787" stroke-width="1.3" /></svg>

      
        ` : '';
const background = parameter === 'wind_speed' ? '' : 
                  parameter === 'temp' ? 'background: pink; border-top: 2px solid purple;' : 
                  'background: blue; border-top: 2px solid green;';
        const displayValue = parameter === 'temp' ? Number.parseInt(obj.temp) :
                            parameter === 'pop' ? Math.round(obj.pop * 100) :
                            Number.parseInt(obj.wind_speed);
        return `<div> 
            <p >${displayValue }</p>  
            <div class='${divClass}' id="${obj.datedt}" style="height: ${height}px; margin:auto; ${background}">${content}</div>
            <p class="plot-time">${String(new Date((obj.datedt + obj.timezone) * 1000).getHours()).padStart(2, '0')}:00</p> 
        </div>`;
    }).join('');
                             
    // Wstawienie divów do kontenera na stronie
    const container = document.querySelector('.plot');
    if (container) {
        container.innerHTML = `${divs}`;

        // Dodanie obsługi kliknięć za pomocą addEventListener
        data.forEach((obj, index) => {
            const div = document.getElementById(obj.datedt);
            if (div) {
                div.addEventListener('click', () => {
                    description(obj, unit); // Wywołanie description z pojedynczym obiektem
                });
            } else {
                console.error(`Error: Div with id ${obj.datedt} not found`);
            }
        });
    } else {
        console.error('Error: Container .plot not found');
    }
}

// Zabezpieczenie przed nadpisywaniem description
window.description = description;