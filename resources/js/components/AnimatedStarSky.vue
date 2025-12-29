<script setup lang="ts">
import { onMounted, ref } from 'vue';

defineProps<{
  quote?: {
    message: string;
    author: string;
  };
  appName?: string;
  appLogo?: boolean;
}>();

interface VerticalLine {
  id: number;
  x: number;
  y1: number;
  y2: number;
  strokeWidth: number;
}

interface CircuitConnection {
  id: number;
  x1: number;
  x2: number;
  y1: number;
  y2: number;
  strokeWidth: number;
  lineIndex1: number;
  lineIndex2: number;
}

interface CircuitNode {
  id: number;
  x: number;
  y: number;
  size: number;
  hasRing: boolean;
}

interface Pulse {
  id: number;
  path: string;
  animationDuration: string;
  animationDelay: string;
  pathLength: number;
}

const verticalLines = ref<VerticalLine[]>([]);
const connections = ref<CircuitConnection[]>([]);
const nodes = ref<CircuitNode[]>([]);
const pulses = ref<Pulse[]>([]);

const xPositions = [8, 15, 25, 32, 45, 52, 60, 72, 78, 88, 95];

onMounted(() => {
  generateCircuit();
});

const generateCircuit = () => {
  const newVerticalLines: VerticalLine[] = [];
  const newConnections: CircuitConnection[] = [];
  const newNodes: CircuitNode[] = [];

  // Generar líneas verticales
  xPositions.forEach((baseX, index) => {
    const x = baseX + (Math.random() - 0.5) * 1.5;
    const y1 = 100;
    const y2 = Math.random() * 35 + 8;

    newVerticalLines.push({
      id: index,
      x,
      y1,
      y2,
      strokeWidth: Math.random() * 0.15 + 0.1, // Thinner lines for realism
    });

    // Nodos intermedios
    const numNodes = Math.floor(Math.random() * 3) + 1;
    for (let i = 0; i < numNodes; i++) {
      const nodeY = y2 + ((y1 - y2) * (i + 1)) / (numNodes + 1);
      newNodes.push({
        id: newNodes.length,
        x,
        y: nodeY,
        size: Math.random() * 1.5 + 1,
        hasRing: Math.random() > 0.7,
      });
    }

    // Nodo terminal superior
    newNodes.push({
      id: newNodes.length,
      x,
      y: y2,
      size: 1.2,
      hasRing: true,
    });
  });

  // Conexiones inclinadas entre líneas adyacentes
  for (let i = 0; i < newVerticalLines.length - 1; i++) {
    if (Math.random() > 0.4) {
      const line1 = newVerticalLines[i];
      const line2 = newVerticalLines[i + 1];

      const minY = Math.max(line1.y2, line2.y2) + 5;
      const maxY = Math.min(line1.y1, line2.y1) - 5;
      const xDist = Math.abs(line2.x - line1.x);

      if (maxY > minY + xDist) {
        // Intentar crear un ángulo de 45 grados (deltaY ~= deltaX)
        const directions = [];
        if (maxY - xDist >= minY) directions.push(1);
        if (maxY >= minY + xDist) directions.push(-1);

        if (directions.length > 0) {
          const dir = directions[Math.floor(Math.random() * directions.length)];
          let startY;
          let endY;

          if (dir === 1) {
            startY = Math.random() * (maxY - xDist - minY) + minY;
            endY = startY + xDist;
          } else {
            startY = Math.random() * (maxY - (minY + xDist)) + (minY + xDist);
            endY = startY - xDist;
          }

          newConnections.push({
            id: newConnections.length,
            x1: line1.x,
            x2: line2.x,
            y1: startY,
            y2: endY,
            strokeWidth: Math.random() * 0.15 + 0.1,
            lineIndex1: i,
            lineIndex2: i + 1,
          });

          // Nodos en las intersecciones (opcional, en el dibujo algunos cruces tienen nodos)
          // Vamos a poner nodos simples en los puntos de giro
          newNodes.push({
            id: newNodes.length,
            x: line1.x,
            y: startY,
            size: 0.8,
            hasRing: false,
          });
          newNodes.push({
            id: newNodes.length,
            x: line2.x,
            y: endY,
            size: 0.8,
            hasRing: false,
          });
        }
      }
    }
  }

  verticalLines.value = newVerticalLines;
  connections.value = newConnections;
  nodes.value = newNodes;

  generatePulses(newVerticalLines, newConnections);
};

const generatePulses = (lines: VerticalLine[], conns: CircuitConnection[]) => {
  // Inicializar un pool pequeño de pulsos
  const maxActivePulses = 5;
  for (let i = 0; i < maxActivePulses; i++) {
    // Escalonar el inicio para que no salgan todos a la vez
    setTimeout(() => {
      spawnPulse(i, lines, conns);
    }, Math.random() * 3000);
  }
};

const spawnPulse = (
  id: number,
  lines: VerticalLine[],
  conns: CircuitConnection[],
) => {
  const lineIndex = Math.floor(Math.random() * lines.length);
  const line = lines[lineIndex];

  // Buscar conexiones relevantes
  const relevantConnections = conns.filter(
    (c) => c.lineIndex1 === lineIndex || c.lineIndex2 === lineIndex,
  );

  let path: string = '';
  let pathLength: number = 0;

  // 70% de probabilidad de tomar una ruta desviada si existe conexión
  if (relevantConnections.length > 0 && Math.random() > 0.3) {
    const conn =
      relevantConnections[
        Math.floor(Math.random() * relevantConnections.length)
      ];
    const isFromLine1 = conn.lineIndex1 === lineIndex;
    const targetLine = isFromLine1
      ? lines[conn.lineIndex2]
      : lines[conn.lineIndex1];

    const startConnY = isFromLine1 ? conn.y1 : conn.y2;
    const endConnY = isFromLine1 ? conn.y2 : conn.y1;

    // Ruta completa: subir -> diagonal -> subir
    path = `M ${line.x} ${line.y1} L ${line.x} ${startConnY} L ${targetLine.x} ${endConnY} L ${targetLine.x} ${targetLine.y2}`;

    const diagonalLen = Math.sqrt(
      Math.pow(targetLine.x - line.x, 2) + Math.pow(endConnY - startConnY, 2),
    );
    pathLength =
      Math.abs(line.y1 - startConnY) +
      diagonalLen +
      Math.abs(endConnY - targetLine.y2);
  } else {
    // Ruta directa vertical
    path = `M ${line.x} ${line.y1} L ${line.x} ${line.y2}`;
    pathLength = Math.abs(line.y1 - line.y2);
  }

  // Velocidad moderada (factor 35) + varianza
  const baseDuration = pathLength / 35;
  const duration = baseDuration * (Math.random() * 0.5 + 0.8); // 0.8x a 1.3x varianza

  // Actualizar o añadir pulso
  const existingIndex = pulses.value.findIndex((p) => p.id === id);
  const pulseData = {
    id,
    path,
    animationDuration: `${duration.toFixed(2)}s`,
    animationDelay: '0s', // Delay manejado por JS, no CSS
    pathLength,
  };

  if (existingIndex >= 0) {
    // Forzar reactividad para reiniciar animación: cambiar key implícitamente al reemplazar objeto
    // pero para asegurar reinicio completo en Vue, a veces necesitamos un v-if toggle o cambiar key.
    // Aquí confiaremos en que al cambiar el path y la duración, y al haber pasado tiempo, se reinicia.
    pulses.value[existingIndex] = pulseData;
  } else {
    pulses.value.push(pulseData);
  }

  // Programar el siguiente ciclo para ESTE pulso
  // Duración animación + pausa aleatoria (2s a 8s)
  const nextSpawnDelay = duration * 1000 + (Math.random() * 6000 + 2000);

  setTimeout(() => {
    // Remover visualmente antes de regenerar (opcional, o simplemente sobrescribir)
    // Para suavidad, lo dejamos hasta que le toque el turno de nuevo.
    // O mejor: Lo ocultamos o esperamos.
    // Al regenerarlo en 'spawnPulse', se le asignará un nuevo path y comenzará a animar de nuevo.
    spawnPulse(id, lines, conns);
  }, nextSpawnDelay);
};
</script>

<template>
  <div
    class="relative hidden h-full flex-col overflow-hidden p-10 lg:flex"
    :class="[
      'bg-linear-to-t from-emerald-50 via-emerald-50/30 to-white',
      'dark:bg-linear-to-t dark:from-emerald-950 dark:via-emerald-900 dark:to-emerald-950',
    ]"
  >
    <svg
      class="absolute inset-0 h-full w-full"
      viewBox="0 0 100 100"
      preserveAspectRatio="xMidYMid slice"
    >
      <defs>
        <!-- Gradiente radial para pulso -->
        <radialGradient id="pulseGradient" cx="50%" cy="50%" r="50%">
          <stop offset="0%" stop-color="oklch(0.648 0.2 131.684 / 1)" />
          <stop offset="60%" stop-color="oklch(0.648 0.2 131.684 / 0.6)" />
          <stop offset="100%" stop-color="oklch(0.648 0.2 131.684 / 0)" />
        </radialGradient>

        <!-- Filtro de brillo sutil -->
        <filter id="glow" x="-200%" y="-200%" width="500%" height="500%">
          <feGaussianBlur stdDeviation="0.5" result="blur1" />
          <feMerge>
            <feMergeNode in="blur1" />
            <feMergeNode in="SourceGraphic" />
          </feMerge>
        </filter>

        <!-- Definir paths para cada pulso -->
        <path
          v-for="pulse in pulses"
          :key="`path-def-${pulse.id}`"
          :id="`pulsePath-${pulse.id}`"
          :d="pulse.path"
          fill="none"
        />
      </defs>

      <!-- Líneas verticales del circuito -->
      <g class="vertical-lines">
        <line
          v-for="line in verticalLines"
          :key="`v-${line.id}`"
          :x1="line.x"
          :y1="line.y1"
          :x2="line.x"
          :y2="line.y2"
          class="circuit-line"
          :stroke-width="line.strokeWidth"
        />
      </g>

      <!-- Conexiones inclinadas -->
      <g class="circuit-connections">
        <line
          v-for="conn in connections"
          :key="`h-${conn.id}`"
          :x1="conn.x1"
          :y1="conn.y1"
          :x2="conn.x2"
          :y2="conn.y2"
          class="circuit-line"
          :stroke-width="conn.strokeWidth"
        />
      </g>

      <!-- Nodos de conexión -->
      <g class="circuit-nodes">
        <g v-for="node in nodes" :key="`node-${node.id}`">
          <circle
            v-if="node.hasRing"
            :cx="node.x"
            :cy="node.y"
            :r="node.size + 0.8"
            class="node-ring"
          />
          <circle
            :cx="node.x"
            :cy="node.y"
            :r="node.size / 2"
            class="circuit-node"
          />
        </g>
      </g>

      <!-- Pulsos de luz animados siguiendo paths exactos -->
      <g class="light-pulses">
        <g v-for="pulse in pulses" :key="`pulse-group-${pulse.id}`">
          <circle r="1" class="pulse-trail" filter="url(#glow)">
            <animateMotion
              :dur="pulse.animationDuration"
              :begin="pulse.animationDelay"
              repeatCount="indefinite"
              rotate="auto"
              calcMode="linear"
              keyPoints="0;1"
              keyTimes="0;1"
            >
              <mpath :href="`#pulsePath-${pulse.id}`" />
            </animateMotion>
            <animate
              attributeName="opacity"
              values="0;0.2;0.2;0"
              keyTimes="0;0.1;0.9;1"
              :dur="pulse.animationDuration"
              :begin="pulse.animationDelay"
              repeatCount="indefinite"
            />
          </circle>

          <!-- Núcleo brillante del pulso -->
          <circle r="0.5" class="light-pulse" filter="url(#glow)">
            <animateMotion
              :dur="pulse.animationDuration"
              :begin="pulse.animationDelay"
              repeatCount="indefinite"
              rotate="auto"
              calcMode="linear"
              keyPoints="0;1"
              keyTimes="0;1"
            >
              <mpath :href="`#pulsePath-${pulse.id}`" />
            </animateMotion>
            <animate
              attributeName="opacity"
              values="0;0.8;0.8;0"
              keyTimes="0;0.1;0.9;1"
              :dur="pulse.animationDuration"
              :begin="pulse.animationDelay"
              repeatCount="indefinite"
            />
          </circle>
        </g>
      </g>
    </svg>

    <!-- Logo -->
    <div
      v-if="appLogo"
      class="relative z-20 flex items-center text-lg font-medium"
    >
      <slot name="logo" />
    </div>

    <!-- Cita -->
    <div v-if="quote" class="relative z-20 mt-auto">
      <blockquote class="space-y-2">
        <p
          class="text-shadow-light dark:text-shadow-dark text-lg text-gray-700 dark:text-gray-100"
        >
          &ldquo;{{ quote.message }}&rdquo;
        </p>
        <footer
          class="text-shadow-light-sm dark:text-shadow-dark-sm text-sm text-gray-500 dark:text-gray-300"
        >
          {{ quote.author }}
        </footer>
      </blockquote>
    </div>
  </div>
</template>

<style>
/* Líneas del circuito - más visibles en modo claro */
.circuit-line {
  stroke: oklch(0.648 0.2 131.684 / 0.4);
  transition: stroke 0.3s ease;
}

.circuit-node {
  fill: oklch(0.648 0.2 131.684 / 0.5);
  transition: fill 0.3s ease;
}

.node-ring {
  fill: none;
  stroke: oklch(0.648 0.2 131.684 / 0.4);
  stroke-width: 0.15;
}

.light-pulse {
  fill: oklch(0.648 0.2 131.684 / 0.9);
}

.pulse-trail {
  fill: oklch(0.648 0.2 131.684 / 0.3);
}

/* Modo oscuro: Restaurar colores antiguos */
.dark .circuit-line {
  stroke: rgba(110, 231, 183, 0.1);
}

.dark .circuit-node {
  fill: rgba(110, 231, 183, 0.2);
}

.dark .node-ring {
  stroke: rgba(110, 231, 183, 0.15);
}

.dark .light-pulse {
  fill: rgba(167, 243, 208, 0.9);
}

.dark .pulse-trail {
  fill: rgba(167, 243, 208, 0.25);
}

/* Sombras de texto */
.text-shadow-light {
  text-shadow:
    0 1px 2px rgba(0, 0, 0, 0.1),
    0 1px 4px rgba(0, 0, 0, 0.05);
}
.text-shadow-light-sm {
  text-shadow:
    0 1px 1px rgba(0, 0, 0, 0.08),
    0 1px 2px rgba(0, 0, 0, 0.04);
}
.text-shadow-dark {
  text-shadow:
    0 1px 2px rgba(0, 0, 0, 0.3),
    0 1px 4px rgba(0, 0, 0, 0.2);
}
.text-shadow-dark-sm {
  text-shadow:
    0 1px 1px rgba(0, 0, 0, 0.25),
    0 1px 2px rgba(0, 0, 0, 0.15);
}
</style>
