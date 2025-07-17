$(function () {
  'use strict'

  // Gráfico de Citas y Tratamientos
  var appointmentsChartCanvas = document.getElementById('appointments-chart').getContext('2d')
  
  var appointmentsChartData = {
    labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
    datasets: [
      {
        label: 'Citas Programadas',
        backgroundColor: 'rgba(54, 162, 235, 0.2)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: 'rgba(54, 162, 235, 1)',
        data: [22, 19, 25, 21, 18, 12, 8]
      },
      {
        label: 'Citas Completadas',
        backgroundColor: 'rgba(75, 192, 192, 0.2)',
        borderColor: 'rgba(75, 192, 192, 1)',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: 'rgba(75, 192, 192, 1)',
        data: [20, 17, 23, 19, 16, 10, 6]
      },
      {
        label: 'Tratamientos Iniciados',
        backgroundColor: 'rgba(255, 206, 86, 0.2)',
        borderColor: 'rgba(255, 206, 86, 1)',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: 'rgba(255, 206, 86, 1)',
        data: [8, 12, 15, 11, 9, 6, 3]
      }
    ]
  }

  var appointmentsChartOptions = {
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
        max: 30
      }
    },
    interaction: {
      intersect: false,
      mode: 'index'
    }
  }

  // Crear el gráfico de citas
  var appointmentsChart = new Chart(appointmentsChartCanvas, {
    type: 'line',
    data: appointmentsChartData,
    options: appointmentsChartOptions
  })

  // Animación para los círculos de progreso de tratamientos
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
    console.log('Actualizando datos del dashboard...');
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

});