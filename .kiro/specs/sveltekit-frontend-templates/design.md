# Design Document

## Overview

This design document outlines the implementation of HTML templates from the maquetado folder into functional SvelteKit components for the SimpleTask Engine frontend. The design focuses on creating reusable components with custom Tailwind classes while maintaining visual fidelity to the original templates.

## Architecture

### Component Hierarchy

```
src/
├── lib/
│   ├── components/
│   │   ├── layout/
│   │   │   ├── Sidebar.svelte
│   │   │   ├── Header.svelte
│   │   │   └── Layout.svelte
│   │   ├── ui/
│   │   │   ├── Button.svelte
│   │   │   ├── Card.svelte
│   │   │   ├── Modal.svelte
│   │   │   ├── Avatar.svelte
│   │   │   ├── Badge.svelte
│   │   │   └── ProgressBar.svelte
│   │   ├── board/
│   │   │   ├── BoardCard.svelte
│   │   │   ├── BoardGrid.svelte
│   │   │   ├── KanbanColumn.svelte
│   │   │   └── TaskCard.svelte
│   │   └── task/
│   │       ├── TaskDetail.svelte
│   │       ├── SubtaskList.svelte
│   │       └── DependencyList.svelte
│   └── stores/
│       ├── boards.js
│       ├── tasks.js
│       └── ui.js
├── routes/
│   ├── +layout.svelte
│   ├── +page.svelte (Dashboard)
│   └── board/
│       └── [id]/
│           └── +page.svelte (Board View)
└── app.css (Custom Tailwind Classes)
```

### Design Patterns

1. **Component Composition**: Each template is broken down into smaller, reusable components
2. **Prop-based Configuration**: Components accept props for customization without inline styles
3. **Store-based State Management**: Svelte stores handle application state
4. **Custom Tailwind Classes**: @apply directive creates reusable component classes

## Components and Interfaces

### Layout Components

#### Sidebar Component
```javascript
// Sidebar.svelte
export let activeItem = 'boards';
export let user = null;
export let boards = [];

// Props:
// - activeItem: string (current navigation item)
// - user: object (user information)
// - boards: array (favorite boards for quick access)
```

#### Header Component
```javascript
// Header.svelte
export let title = '';
export let breadcrumbs = [];
export let showSearch = true;
export let actions = [];

// Props:
// - title: string (page title)
// - breadcrumbs: array (navigation breadcrumbs)
// - showSearch: boolean (show/hide search bar)
// - actions: array (header action buttons)
```

### UI Components

#### Card Component
```javascript
// Card.svelte
export let variant = 'default'; // 'default', 'board', 'task'
export let hover = true;
export let clickable = false;

// Props:
// - variant: string (card style variant)
// - hover: boolean (enable hover effects)
// - clickable: boolean (enable click interactions)
```

#### Button Component
```javascript
// Button.svelte
export let variant = 'primary'; // 'primary', 'secondary', 'ghost'
export let size = 'md'; // 'sm', 'md', 'lg'
export let disabled = false;
export let loading = false;

// Props:
// - variant: string (button style)
// - size: string (button size)
// - disabled: boolean (disabled state)
// - loading: boolean (loading state)
```

#### Modal Component
```javascript
// Modal.svelte
export let open = false;
export let size = 'md'; // 'sm', 'md', 'lg', 'xl'
export let position = 'center'; // 'center', 'right'

// Props:
// - open: boolean (modal visibility)
// - size: string (modal size)
// - position: string (modal position)
```

### Board Components

#### BoardCard Component
```javascript
// BoardCard.svelte
export let board = {};
export let onClick = null;

// Props:
// - board: object (board data with title, description, progress, members)
// - onClick: function (click handler)
```

#### KanbanColumn Component
```javascript
// KanbanColumn.svelte
export let column = {};
export let tasks = [];
export let onTaskMove = null;
export let onTaskClick = null;

// Props:
// - column: object (column data with title, color, status)
// - tasks: array (tasks in this column)
// - onTaskMove: function (drag and drop handler)
// - onTaskClick: function (task click handler)
```

#### TaskCard Component
```javascript
// TaskCard.svelte
export let task = {};
export let draggable = true;
export let onClick = null;

// Props:
// - task: object (task data with title, description, assignees, etc.)
// - draggable: boolean (enable drag and drop)
// - onClick: function (click handler)
```

### Task Components

#### TaskDetail Component
```javascript
// TaskDetail.svelte
export let task = {};
export let open = false;
export let onClose = null;
export let onUpdate = null;

// Props:
// - task: object (complete task data)
// - open: boolean (modal visibility)
// - onClose: function (close handler)
// - onUpdate: function (task update handler)
```

## Data Models

### Board Model
```javascript
{
  id: number,
  title: string,
  description: string,
  user_id: number,
  created_at: string,
  updated_at: string,
  deleted_at: string | null,
  // When loaded with relationships:
  lists?: [
    {
      id: number,
      board_id: number,
      title: string,
      order: number,
      tasks?: Task[]
    }
  ]
}
```

### List Model (Column)
```javascript
{
  id: number,
  board_id: number,
  title: string,
  order: number,
  created_at: string,
  updated_at: string,
  deleted_at: string | null,
  // When loaded with relationships:
  tasks?: Task[]
}
```

### Task Model
```javascript
{
  id: number,
  list_id: number,
  title: string,
  description: string,
  status: string, // 'todo', 'in_progress', 'blocked', 'done'
  order: number,
  created_at: string,
  updated_at: string,
  deleted_at: string | null,
  // When loaded with relationships:
  subtasks?: Subtask[],
  dependencies?: TaskDependency[]
}
```

### Subtask Model
```javascript
{
  id: number,
  task_id: number,
  title: string,
  is_completed: boolean,
  created_at: string,
  updated_at: string,
  deleted_at: string | null
}
```

### TaskDependency Model
```javascript
{
  id: number,
  task_id: number,
  depends_on_task_id: number,
  created_at: string,
  updated_at: string,
  deleted_at: string | null
}
```

### User Model
```javascript
{
  id: number,
  name: string,
  email: string,
  created_at: string,
  updated_at: string
}
```

## Tailwind Configuration and Custom Classes

### Extended Tailwind Config
```javascript
// tailwind.config.js
export default {
  content: ['./src/**/*.{html,js,svelte}'],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        primary: '#137fec',
        'primary-dark': '#0f65bd',
        'background-light': '#f6f7f8',
        'background-dark': '#111a22',
        'surface-light': '#ffffff',
        'surface-dark': '#1c2936',
        'border-light': '#e5e7eb',
        'border-dark': '#2d3b4b',
        'text-main-light': '#111827',
        'text-main-dark': '#ffffff',
        'text-sec-light': '#6b7280',
        'text-sec-dark': '#92adc9'
      },
      fontFamily: {
        display: ['Inter', 'sans-serif']
      }
    }
  },
  plugins: []
};
```

### Custom Component Classes
```css
/* app.css */
@import 'tailwindcss';

@layer components {
  /* Layout Classes */
  .app-layout {
    @apply flex h-screen w-full overflow-hidden bg-background-light dark:bg-background-dark text-text-main-light dark:text-text-main-dark font-display antialiased transition-colors duration-200;
  }
  
  .sidebar {
    @apply w-64 flex-shrink-0 flex flex-col bg-surface-light dark:bg-background-dark border-r border-border-light dark:border-border-dark h-full transition-colors duration-200;
  }
  
  .main-content {
    @apply flex-1 flex flex-col h-full overflow-hidden relative bg-background-light dark:bg-background-dark;
  }

  /* Card Classes */
  .board-card {
    @apply group flex flex-col rounded-xl bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark hover:border-primary/30 dark:hover:border-primary/50 hover:shadow-lg hover:shadow-primary/5 dark:hover:shadow-none transition-all duration-300 overflow-hidden h-full cursor-pointer;
  }
  
  .task-card {
    @apply group bg-white dark:bg-slate-800 p-4 rounded-lg shadow-sm border border-slate-100 dark:border-transparent hover:border-primary/50 dark:hover:border-primary/50 cursor-pointer transition-all hover:shadow-md;
  }
  
  .create-board-card {
    @apply group flex flex-col items-center justify-center min-h-[200px] h-full rounded-xl border-2 border-dashed border-border-light dark:border-border-dark hover:border-primary/50 hover:bg-primary/5 dark:hover:bg-primary/10 transition-all duration-300 cursor-pointer;
  }

  /* Button Classes */
  .btn-primary {
    @apply flex items-center justify-center gap-2 px-4 py-2 bg-primary hover:bg-primary-dark text-white text-sm font-bold rounded-lg shadow-md transition-colors shadow-primary/20;
  }
  
  .btn-secondary {
    @apply flex items-center justify-center gap-2 px-4 py-2 bg-slate-100 dark:bg-surface-dark hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-white transition-colors rounded-lg;
  }
  
  .btn-ghost {
    @apply flex items-center gap-2 px-3 py-2 rounded-lg text-text-sec-light dark:text-text-sec-dark hover:bg-slate-100 dark:hover:bg-surface-dark hover:text-primary transition-colors;
  }

  /* Navigation Classes */
  .nav-item {
    @apply flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-sec-light dark:text-text-sec-dark hover:bg-slate-100 dark:hover:bg-surface-dark hover:text-primary transition-colors group;
  }
  
  .nav-item-active {
    @apply flex items-center gap-3 px-3 py-2.5 rounded-lg bg-primary/10 dark:bg-surface-dark text-primary dark:text-white;
  }

  /* Form Classes */
  .form-input {
    @apply block w-full px-3 py-2 border border-border-light dark:border-border-dark rounded-lg bg-white dark:bg-surface-dark text-text-main-light dark:text-text-main-dark placeholder-text-sec-light dark:placeholder-text-sec-dark focus:ring-2 focus:ring-primary focus:border-transparent shadow-sm text-sm transition-all;
  }
  
  .search-input {
    @apply block w-full pl-10 pr-3 py-2.5 border-none rounded-lg bg-white dark:bg-surface-dark text-text-main-light dark:text-text-main-dark placeholder-text-sec-light dark:placeholder-text-sec-dark focus:ring-2 focus:ring-primary shadow-sm text-sm transition-all;
  }

  /* Status Classes */
  .status-badge {
    @apply inline-flex items-center px-2 py-0.5 rounded text-xs font-medium;
  }
  
  .status-todo {
    @apply bg-slate-100 text-slate-800 dark:bg-slate-900/30 dark:text-slate-300;
  }
  
  .status-progress {
    @apply bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300;
  }
  
  .status-blocked {
    @apply bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300;
  }
  
  .status-done {
    @apply bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300;
  }

  /* Progress Classes */
  .progress-bar {
    @apply w-full bg-slate-200 dark:bg-slate-700 rounded-full h-1.5 overflow-hidden;
  }
  
  .progress-fill {
    @apply h-full rounded-full transition-all duration-300;
  }

  /* Avatar Classes */
  .avatar {
    @apply rounded-full object-cover;
  }
  
  .avatar-sm {
    @apply w-6 h-6;
  }
  
  .avatar-md {
    @apply w-8 h-8;
  }
  
  .avatar-lg {
    @apply w-10 h-10;
  }
  
  .avatar-group {
    @apply flex -space-x-2;
  }

  /* Modal Classes */
  .modal-backdrop {
    @apply fixed inset-0 bg-black/60 backdrop-blur-sm z-50;
  }
  
  .modal-drawer {
    @apply fixed top-0 right-0 h-full w-full md:max-w-2xl bg-background-light dark:bg-background-dark shadow-2xl z-50 flex flex-col border-l border-border-light dark:border-border-dark;
  }
  
  .modal-header {
    @apply h-16 px-6 border-b border-border-light dark:border-border-dark flex items-center justify-between bg-surface-light/50 dark:bg-background-dark/95 backdrop-blur shrink-0;
  }

  /* Kanban Classes */
  .kanban-board {
    @apply flex h-full gap-6 min-w-max pb-2;
  }
  
  .kanban-column {
    @apply w-80 flex-shrink-0 flex flex-col h-full rounded-xl bg-slate-100 dark:bg-surface-dark border border-slate-200 dark:border-border-dark/50 shadow-sm;
  }
  
  .kanban-column-header {
    @apply p-4 flex items-center justify-between border-b border-slate-200 dark:border-border-dark/50 bg-white/50 dark:bg-transparent backdrop-blur-sm rounded-t-xl sticky top-0 z-10;
  }
  
  .kanban-column-active {
    @apply border-t-4 border-t-primary;
  }
  
  .kanban-column-blocked {
    @apply bg-red-50 dark:bg-red-950/10 border-t-4 border-t-red-500;
  }

  /* Utility Classes */
  .custom-scrollbar::-webkit-scrollbar {
    @apply w-1.5;
  }
  
  .custom-scrollbar::-webkit-scrollbar-track {
    @apply bg-transparent;
  }
  
  .custom-scrollbar::-webkit-scrollbar-thumb {
    @apply bg-border-dark rounded-full;
  }
}
```

## Page Implementations

### Dashboard Page (`/`)
- Uses `BoardGrid` component to display board cards
- Includes "Create New Board" card
- Implements search and filter functionality
- Shows user's recent and favorite boards

### Board View Page (`/board/[id]`)
- Uses `KanbanColumn` components for each status
- Implements drag and drop between columns
- Shows board header with team information
- Opens `TaskDetail` modal when task is clicked

### Task Detail Modal
- Slides in from the right as a drawer
- Shows complete task information
- Allows editing of task properties
- Manages subtasks and dependencies

## Error Handling

### Component Error Boundaries
- Each major component handles its own error states
- Loading states for async operations
- Graceful degradation for missing data

### API Error Handling
- Network error recovery
- Validation error display
- Optimistic updates with rollback

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Component Data Rendering Consistency
*For any* component that receives data props (board data, task data, user data), all required fields specified in the component interface should be rendered in the DOM output
**Validates: Requirements 1.2, 2.2, 3.2, 3.3, 3.4**

### Property 2: Navigation State Consistency  
*For any* navigation action (clicking cards, using router, browser navigation), the application state should correctly reflect the current route and update breadcrumbs accordingly
**Validates: Requirements 1.3, 3.1, 6.2, 6.3, 6.4, 6.5**

### Property 3: Component Reusability
*For any* reusable component (Card, Button, Modal, etc.), the component should render correctly and maintain consistent behavior regardless of the context or props provided
**Validates: Requirements 4.1, 4.2, 4.3, 4.4, 4.5**

### Property 4: Drag and Drop State Management
*For any* task drag and drop operation, the task's column assignment should update correctly unless blocked by business rules (dependencies)
**Validates: Requirements 2.3, 2.4**

### Property 5: Interactive Feedback Consistency
*For any* interactive element (buttons, links, form inputs), hovering and clicking should provide appropriate visual feedback and trigger expected actions
**Validates: Requirements 8.1, 8.2**

### Property 6: Form Validation Behavior
*For any* form input with validation rules, invalid data should trigger error states and prevent submission until corrected
**Validates: Requirements 8.3**

### Property 7: Progress Calculation Accuracy
*For any* task with subtasks, the progress bar percentage should accurately reflect the ratio of completed subtasks to total subtasks
**Validates: Requirements 8.4**

### Property 8: Modal State Management
*For any* modal or drawer component, opening and closing should correctly manage application state and return to the previous view
**Validates: Requirements 3.5**

### Property 9: Layout Structure Consistency
*For any* page or view, the required layout elements (sidebar, header, main content) should be present and properly structured
**Validates: Requirements 1.1, 1.5, 2.1, 2.5**

### Property 10: Async Operation Loading States
*For any* asynchronous operation (API calls, navigation), appropriate loading indicators should be displayed during the operation
**Validates: Requirements 8.5**

## Error Handling

### Component Error Boundaries
- Each major component handles its own error states
- Loading states for async operations
- Graceful degradation for missing data

### API Error Handling
- Network error recovery
- Validation error display
- Optimistic updates with rollback

## Testing Strategy

The testing approach will use a dual strategy combining unit tests and property-based tests to ensure comprehensive coverage and correctness.

### Unit Testing
- Component rendering tests for each Svelte component
- User interaction tests (clicks, form submissions, drag and drop)
- Props validation and default value tests
- Store state management tests
- Route navigation tests
- Specific examples for CSS class definitions and responsive breakpoints

### Property-Based Testing
Property-based tests will validate universal behaviors across the application using generated test data. Each property test should run a minimum of 100 iterations and be tagged with the format: **Feature: sveltekit-frontend-templates, Property {number}: {property_text}**

**Dual Testing Balance:**
- Unit tests focus on specific examples, edge cases, and integration points
- Property tests handle comprehensive input coverage through randomization
- Both approaches are complementary and necessary for complete validation