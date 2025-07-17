$(function () {
  'use strict'

  // Gráfico de Servicios y Ventas
  var servicesChartCanvas = document.getElementById('services-chart').getContext('2d')
  
  var servicesChartData = {
    labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
    datasets: [
      {
        label: 'Cortes Realizados',
        backgroundColor: 'rgba(54, 162, 235, 0.2)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: 'rgba(54, 162, 235, 1)',
        data: [28, 24, 32, 26, 22, 18, 14]
      },
      {
        label: 'Ventas Servicios ($)',
        backgroundColor: 'rgba(75, 192, 192, 0.2)',
        borderColor: 'rgba(75, 192, 192, 1)',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: 'rgba(75, 192, 192, 1)',
        data: [2800, 2400, 3200, 2600, 2200, 1800, 1400]
      },
      {
        label: 'Ventas Productos ($)',
        backgroundColor: 'rgba(255, 206, 86, 0.2)',
        borderColor: 'rgba(255, 206, 86, 1)',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: 'rgba(255, 206, 86, 1)',
        data: [450, 380, 520, 420, 350, 280, 220]
      }
    ]
  }

  var servicesChartOptions = {
    maintainAspectRatio: false,
    responsive: true,
    plugins: {
      legend: {
        display: true,
        position: 'top'
      }
    },
    scales: {
      x: {
        grid: {
          display: false
        }
      },
      y: {
        grid: {
          display: true,
          color: 'rgba(0,0,0,0.1)'
        },
        beginAtZero: true,
        max: 3500
      }
    },
    interaction: {
      intersect: false,
      mode: 'index'
    }
  }

  // Crear el gráfico de servicios
  var servicesChart = new Chart(servicesChartCanvas, {
    type: 'line',
    data: servicesChartData,
    options: servicesChartOptions
  })

  // Animación para los círculos de progreso de servicios populares
  $('.progress-ring-circle').each(function() {
    var circle = $(this);
    var percent = circle.closest('.text-center').find('strong').text().replace('%', '');
    var circumference = 2 * Math.PI * 30; // radio = 30
    var offset = circumference - (percent / 100) * circumference;
    
    circle.css({
      'stroke-dasharray': circumference,
      'stroke-dashoffset': offset
    });
  });

  // Actualizar datos cada 30 segundos (simulación)
  setInterval(function() {
    // Aquí puedes agregar llamadas AJAX para actualizar los datos en tiempo real
    console.log('Actualizando datos del dashboard de barbería...');
  }, 30000);

  // Configurar tooltips para mejor experiencia
  $('[data-bs-toggle="tooltip"]').tooltip();

  // Efectos hover para las cards de estadísticas
  $('.card.text-white').hover(
    function() {
      $(this).addClass('shadow-lg').css('transform', 'translateY(-2px)');
    },
    function() {
      $(this).removeClass('shadow-lg').css('transform', 'translateY(0)');
    }
  );

  // Animación de las barras de progreso de barberos
  $('.progress-bar').each(function() {
    var progressBar = $(this);
    var width = progressBar.css('width');
    progressBar.css('width', '0%');
    
    setTimeout(function() {
      progressBar.animate({
        width: width
      }, 1000);
    }, 500);
  });

});