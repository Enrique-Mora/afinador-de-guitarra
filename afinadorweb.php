<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Afinador de Guitarra</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 50px;
        }

        .status {
            margin-top: 20px;
            font-size: 20px;
            color: green;
        }

        h1 {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <h1>Afinador de Guitarra</h1>

    <button onclick="startTuning()">Iniciar Afinación</button>
    
    <div class="status" id="status">Esperando...</div>
    
    <div id="frequency">Frecuencia: -- Hz</div>
    <div id="note">Nota: --</div>

    <script>
        const notesFrequencies = {
            'E2': 82.41,  // Cuerda 6 (E grave)
            'A2': 110.00, // Cuerda 5 (A)
            'D3': 146.83, // Cuerda 4 (D)
            'G3': 196.00, // Cuerda 3 (G)
            'B3': 246.94, // Cuerda 2 (B)
            'E4': 329.63  // Cuerda 1 (e aguda)
        };

        let audioContext;
        let analyser;
        let microphone;
        let dataArray;
        let bufferLength;

        function startTuning() {
            navigator.mediaDevices.getUserMedia({ audio: true })
                .then(stream => {
                    audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    analyser = audioContext.createAnalyser();
                    analyser.fftSize = 2048;

                    microphone = audioContext.createMediaStreamSource(stream);
                    microphone.connect(analyser);

                    bufferLength = analyser.frequencyBinCount;
                    dataArray = new Float32Array(bufferLength);

                    document.getElementById('status').textContent = "Afinando...";
                    analyzeSound();
                })
                .catch(err => {
                    console.error('Error al acceder al micrófono', err);
                });
        }

        function analyzeSound() {
            analyser.getFloatTimeDomainData(dataArray);

            const frequency = autoCorrelate(dataArray, audioContext.sampleRate);

            if (frequency !== -1) {
                document.getElementById('frequency').textContent = `Frecuencia: ${frequency.toFixed(2)} Hz`;
                const note = getClosestNote(frequency);
                document.getElementById('note').textContent = `Nota: ${note}`;
            } else {
                document.getElementById('frequency').textContent = 'Frecuencia: -- Hz';
                document.getElementById('note').textContent = 'Nota: --';
            }

            requestAnimationFrame(analyzeSound);
        }

        function autoCorrelate(buffer, sampleRate) {
            let SIZE = buffer.length;
            let rms = 0;

            for (let i = 0; i < SIZE; i++) {
                rms += buffer[i] * buffer[i];
            }

            rms = Math.sqrt(rms / SIZE);
            if (rms < 0.01) return -1;

            let r1 = 0, r2 = SIZE - 1, thres = 0.2;
            for (let i = 0; i < SIZE / 2; i++) {
                if (Math.abs(buffer[i]) < thres) {
                    r1 = i;
                    break;
                }
            }

            for (let i = 1; i < SIZE / 2; i++) {
                if (Math.abs(buffer[SIZE - i]) < thres) {
                    r2 = SIZE - i;
                    break;
                }
            }

            buffer = buffer.slice(r1, r2);
            SIZE = buffer.length;

            let c = new Array(SIZE).fill(0);
            for (let i = 0; i < SIZE; i++) {
                for (let j = 0; j < SIZE - i; j++) {
                    c[i] = c[i] + buffer[j] * buffer[j + i];
                }
            }

            let d = 0;
            while (c[d] > c[d + 1]) d++;
            let maxval = -1, maxpos = -1;
            for (let i = d; i < SIZE; i++) {
                if (c[i] > maxval) {
                    maxval = c[i];
                    maxpos = i;
                }
            }

            let T0 = maxpos;

            return sampleRate / T0;
        }

        function getClosestNote(frequency) {
            let closestNote = '';
            let minDiff = Infinity;

            for (let note in notesFrequencies) {
                const diff = Math.abs(frequency - notesFrequencies[note]);
                if (diff < minDiff) {
                    minDiff = diff;
                    closestNote = note;
                }
            }

            return closestNote;
        }
    </script>
</body>
</html>
