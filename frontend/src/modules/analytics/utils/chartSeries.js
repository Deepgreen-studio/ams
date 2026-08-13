export function lineSeriesFromRows(rows = [], valueKey = 'value') {
  const list = Array.isArray(rows) ? rows : [];

  return {
    labels: list.map((row) => row.date || row.bucket || row.label || ''),
    values: list.map((row) => Number(row[valueKey] ?? row.value ?? 0)),
  };
}

export function lineChartProps(rows = [], valueKey = 'value', seriesLabel = 'Value') {
  const chart = lineSeriesFromRows(rows, valueKey);

  return {
    labels: chart.labels,
    series: [{ key: valueKey, label: seriesLabel, values: chart.values }],
  };
}
