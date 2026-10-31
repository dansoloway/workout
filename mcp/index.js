#!/usr/bin/env node

import { McpServer } from '@modelcontextprotocol/sdk/server/mcp.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { z } from 'zod';

const baseUrl = (process.env.WORKOUT_MCP_URL ?? '').replace(/\/$/, '');
const token = process.env.WORKOUT_MCP_TOKEN ?? '';

if (!baseUrl) {
    console.error('WORKOUT_MCP_URL is required, e.g. https://danielsoloway.com/workout');
    process.exit(1);
}

if (!token) {
    console.error('WORKOUT_MCP_TOKEN is required');
    process.exit(1);
}

async function api(path, query = {}) {
    const url = new URL(`${baseUrl}/api/mcp/${path}`);

    for (const [key, value] of Object.entries(query)) {
        if (value !== undefined && value !== null && value !== '') {
            url.searchParams.set(key, String(value));
        }
    }

    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
        },
    });

    const text = await response.text();
    let body;

    try {
        body = text ? JSON.parse(text) : null;
    } catch {
        body = { message: text };
    }

    if (!response.ok) {
        throw new Error(body?.message ?? `HTTP ${response.status}`);
    }

    return body;
}

function jsonResult(data) {
    return {
        content: [{ type: 'text', text: JSON.stringify(data, null, 2) }],
    };
}

const server = new McpServer({
    name: 'morning-workout',
    version: '1.0.0',
});

server.registerTool(
    'get_day',
    {
        description: 'Get calisthenics, tennis/swimming workouts, and stretching/meditation for one day. Defaults to today.',
        inputSchema: {
            date: z.string().regex(/^\d{4}-\d{2}-\d{2}$/).optional().describe('Date as YYYY-MM-DD. Defaults to today.'),
        },
    },
    async ({ date }) => jsonResult(await api('day', { date })),
);

server.registerTool(
    'get_summary',
    {
        description: 'Get week or month totals. Workout total counts calisthenics, tennis, and swimming. Stretching and meditation are listed separately with minutes.',
        inputSchema: {
            period: z.enum(['week', 'month']).describe('week = current Sunday–Saturday; month = a calendar month'),
            month: z.string().regex(/^\d{4}-\d{2}$/).optional().describe('Month as YYYY-MM when period is month. Defaults to the current month.'),
        },
    },
    async ({ period, month }) => jsonResult(await api('summary', { period, month })),
);

server.registerTool(
    'list_recent',
    {
        description: 'List recent days that have anything logged (workouts or practices).',
        inputSchema: {
            days: z.number().int().min(1).max(90).optional().describe('Look back this many calendar days. Defaults to 14.'),
        },
    },
    async ({ days }) => jsonResult(await api('recent', { days })),
);

const transport = new StdioServerTransport();
await server.connect(transport);
