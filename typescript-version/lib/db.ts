import fs from 'fs';
import path from 'path';

const DB_FILE = '/tmp/db.json';

export type Programme = 'Sandwich' | 'IDELL';

export interface Student {
  id: string;
  programme: Programme;
  first_name: string;
  middle_name?: string;
  last_name: string;
  reg_number: string;
  password?: string;
  blood_group: string;
  passport_photo: string; // Base64 data URL
  faculty: string;
  department: string;
  course_of_study?: string;
  created_at: string;
  status: 'pending' | 'id_generated';
  admin_note?: string;
}

export interface Admin {
  id: string;
  username: string;
  password_hash: string;
  programme_managed: Programme | 'SuperAdmin';
  created_at: string;
}

interface DBState {
  students: Student[];
  admins: Admin[];
}

const defaultState: DBState = {
  students: [],
  admins: [
    {
      id: 'admin-1',
      username: 'admin',
      password_hash: 'password', // Simulating a hash for demo
      programme_managed: 'SuperAdmin',
      created_at: new Date().toISOString()
    },
    {
      id: 'admin-2',
      username: 'sandwich_admin',
      password_hash: 'password',
      programme_managed: 'Sandwich',
      created_at: new Date().toISOString()
    },
    {
      id: 'admin-3',
      username: 'idell_admin',
      password_hash: 'password',
      programme_managed: 'IDELL',
      created_at: new Date().toISOString()
    }
  ]
};

function readDb(): DBState {
  if (!fs.existsSync(DB_FILE)) {
    writeDb(defaultState);
    return defaultState;
  }
  try {
    const data = fs.readFileSync(DB_FILE, 'utf8');
    return JSON.parse(data);
  } catch (e) {
    return defaultState;
  }
}

function writeDb(state: DBState) {
  fs.writeFileSync(DB_FILE, JSON.stringify(state, null, 2));
}

export function getStudents(programme?: Programme | 'SuperAdmin'): Student[] {
  const db = readDb();
  if (programme && programme !== 'SuperAdmin') {
    return db.students.filter(s => s.programme === programme);
  }
  return db.students;
}

export function getStudentByReg(reg_number: string): Student | undefined {
  const db = readDb();
  return db.students.find(s => s.reg_number === reg_number);
}

export function registerStudent(student: Omit<Student, 'id' | 'created_at' | 'status' | 'password'>) {
  const db = readDb();
  if (db.students.find(s => s.reg_number === student.reg_number)) {
    throw new Error('Student with this registration number already exists.');
  }
  
  const newStudent: Student = {
    ...student,
    id: `stu-${Date.now()}-${Math.random().toString(36).substring(2, 9)}`,
    password: student.reg_number,
    created_at: new Date().toISOString(),
    status: 'pending'
  };
  
  db.students.push(newStudent);
  writeDb(db);
  return newStudent;
}

export function deleteStudentById(id: string) {
  const db = readDb();
  db.students = db.students.filter(s => s.id !== id);
  writeDb(db);
}

export function updateStudentPassword(id: string, newPassword: string) {
  const db = readDb();
  const index = db.students.findIndex(s => s.id === id);
  if (index !== -1) {
    db.students[index].password = newPassword;
    writeDb(db);
  }
}

export function updateStudentNote(id: string, note: string) {
  const db = readDb();
  const index = db.students.findIndex(s => s.id === id);
  if (index !== -1) {
    db.students[index].admin_note = note;
    writeDb(db);
  }
}

export function getStudentById(id: string): Student | undefined {
  const db = readDb();
  return db.students.find(s => s.id === id);
}

export function updateStudentStatus(reg_number: string, status: Student['status']) {
  const db = readDb();
  const index = db.students.findIndex(s => s.reg_number === reg_number);
  if (index !== -1) {
    db.students[index].status = status;
    writeDb(db);
  }
}

export function getAdmin(username: string): Admin | undefined {
  const db = readDb();
  return db.admins.find(a => a.username === username);
}
