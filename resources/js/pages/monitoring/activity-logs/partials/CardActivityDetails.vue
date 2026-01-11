<script setup lang="ts">
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { ActivityLog } from '@/types';
import { computed } from 'vue';

interface GenericModel {
  [index: string]: string | number | boolean | null;
}

interface ComparisonResult {
  key: string;
  value1: string | number | boolean | null;
  value2: string | number | boolean | null;
  areEqual: boolean;
}

const props = defineProps<{
  log: ActivityLog;
}>();

const cardHeader = computed(() => {
  const data: {
    css: string;
    description: string | null;
    title: string;
  } = {
    css: '',
    description: '',
    title: '',
  };

  data.title = props.log.event.toUpperCase();
  switch (props.log.event) {
    case 'inicio de sesión':
    case 'cierre de sesión':
    case 'inicio de sesión fallido':
    case 'bloqueo':
    case 'verificación':
    case 'cambio de contraseña':
      data.css = 'text-primary';
      data.description = 'Datos técnicos de la petición';
      break;
    case 'autorización':
      data.css = 'text-primary';
      data.description =
        'Datos técnicos de los objetos procesados en la autorización';
      break;
    case 'creación':
    case 'restauración':
    case 'activación':
      data.css = 'text-green-500';
      data.description = `${props.log.causer.name} ${props.log.description}`;
      break;
    case 'modificación':
    case 'desactivación':
      data.css = 'text-amber-500';
      data.description = `${props.log.causer.name} ${props.log.description}`;
      break;
    case 'eliminación':
      data.css = 'text-red-500';
      data.description = `${props.log.causer.name} ${props.log.description}`;
      break;

    default:
      data.css = '';
      data.description = 'Detalle del evento.';
      data.title = 'Evento';
      break;
  }

  return data;
});

const authorizedObjects = computed(() => {
  const authObjects: { [index: string]: any } = {};

  Object.keys(props.log.properties).forEach((d) => {
    if (d !== 'request' && d !== 'causer') {
      // @ts-expect-error: ignorar error de TypeScript
      authObjects[d] = props.log.properties[d];
    }
  });

  return authObjects;
});

function compareObjects(obj1: GenericModel, obj2: GenericModel) {
  const comparisonResults: Array<ComparisonResult> = [];
  const allKeys = new Set([...Object.keys(obj1), ...Object.keys(obj2)]);

  allKeys.forEach((key) => {
    const value1 = obj1[key];
    const value2 = obj2[key];

    let areEqual = true;
    let displayValue1 = value1;
    let displayValue2 = value2;

    // Manejo de tipos de datos complejos (objetos y arrays)
    if (
      typeof value1 === 'object' &&
      value1 !== null &&
      typeof value2 === 'object' &&
      value2 !== null
    ) {
      if (Array.isArray(value1) && Array.isArray(value2)) {
        // Comparar arrays (el orden no importa si sólo comparamos elementos)
        // @ts-expect-error: la propiedad no existe en el objeto
        if (
          value1.length !== value2.length ||
          !value1.every((item) => value2.includes(item)) ||
          !value2.every((item) => value1.includes(item))
        ) {
          areEqual = false;
        }
        displayValue1 = JSON.stringify(value1);
        displayValue2 = JSON.stringify(value2);
      } else {
        // Comparar objetos anidados (superficialmente, se puede extender esto para una comparación profunda)
        if (JSON.stringify(value1) !== JSON.stringify(value2)) {
          areEqual = false;
        }
        displayValue1 = JSON.stringify(value1);
        displayValue2 = JSON.stringify(value2);
      }
    } else if (value1 !== value2) {
      areEqual = false;
    }

    comparisonResults.push({
      key: key,
      value1: displayValue1,
      value2: displayValue2,
      areEqual: areEqual,
    });
  });

  return comparisonResults;
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle :class="cardHeader.css">{{ cardHeader.title }}</CardTitle>
      <CardDescription>{{ cardHeader.description }}</CardDescription>
    </CardHeader>
    <CardContent
      v-if="log.event === 'creación' || log.event === 'restauración'"
    >
      <Table>
        <TableCaption>Datos del registro creado.</TableCaption>
        <TableHeader>
          <TableRow>
            <TableHead>Propiedad</TableHead>
            <TableHead>Valor</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow
            v-for="(value, key) in log.properties.attributes"
            :key="key"
          >
            <TableCell class="font-mono text-xs">{{ key }}</TableCell>
            <TableCell class="font-mono text-xs">{{ value }}</TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </CardContent>
    <CardContent v-else-if="log.event === 'eliminación'">
      <Table>
        <TableCaption>Datos del registro eliminado.</TableCaption>
        <TableHeader>
          <TableRow>
            <TableHead>Propiedad</TableHead>
            <TableHead>Valor</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="(value, key) in log.properties.old" :key="key">
            <TableCell class="font-mono text-xs">{{ key }}</TableCell>
            <TableCell class="font-mono text-xs">{{ value }}</TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </CardContent>
    <CardContent
      v-else-if="
        log.event === 'modificación' ||
        log.event === 'activación' ||
        log.event === 'desactivación'
      "
    >
      <Table>
        <TableCaption>Datos del registro modificado.</TableCaption>
        <TableHeader>
          <TableRow>
            <TableHead>Propiedad</TableHead>
            <TableHead class="text-right">Valor Anterior</TableHead>
            <TableHead>Valor Actual</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody v-if="log.properties.attributes && log.properties.old">
          <TableRow
            v-for="(item, i) in compareObjects(
              log.properties.old,
              log.properties.attributes,
            )"
            :key="i"
          >
            <TableCell class="font-mono text-xs">{{ item.key }}</TableCell>
            <TableCell
              class="text-right font-mono text-xs text-muted-foreground"
              :class="{ 'bg-red-100 font-medium text-red-700': !item.areEqual }"
            >
              {{ item.value1 !== undefined ? item.value1 : 'N/A' }}
            </TableCell>
            <TableCell
              class="font-mono text-xs text-muted-foreground"
              :class="{
                'bg-green-100 font-medium text-green-700': !item.areEqual,
                'text-muted-foreground': item.areEqual,
              }"
            >
              {{ item.value2 !== undefined ? item.value2 : 'N/A' }}
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </CardContent>
    <CardContent v-else-if="log.event === 'autorización'">
      <div class="grid w-full items-center gap-4">
        <div class="space-y-1">
          <p class="text-sm leading-none font-medium">
            Propiedades de los Registros:
          </p>
          <pre class="text-xs text-pretty text-muted-foreground">{{
            authorizedObjects
          }}</pre>
        </div>
      </div>
    </CardContent>
    <CardContent v-else>
      <div class="grid w-full items-center gap-4">
        <div class="space-y-1">
          <pre class="text-xs text-pretty text-muted-foreground">{{
            log.properties
          }}</pre>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
